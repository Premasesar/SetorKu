<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Layanan siklus hidup permintaan penjemputan sampah — PBI-07..PBI-09.
 *
 * Alur status ketat: menunggu -> ditolak | terjadwal -> selesai.
 */
class PickupService
{
    public function __construct(private readonly DepositService $depositService)
    {
    }

    /**
     * Buat pengajuan penjemputan baru oleh nasabah dengan status awal selalu 'menunggu'.
     *
     * @param  array{requested_date: string, address: string, latitude: float|string, longitude: float|string, notes?: ?string}  $data
     */
    public function submitRequest(Customer $customer, array $data): PickupRequest
    {
        $pickup = new PickupRequest([
            'requested_date' => $data['requested_date'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'notes' => $data['notes'] ?? null,
        ]);

        $pickup->customer_id = $customer->id;
        $pickup->status = PickupRequest::STATUS_MENUNGGU;
        $pickup->save();

        return $pickup;
    }

    /**
     * Tolak pengajuan penjemputan (hanya oleh Admin dan hanya dari status 'menunggu').
     */
    public function reject(PickupRequest $pickupRequest, string $reason, User $admin): PickupRequest
    {
        abort_unless($admin->isAdmin(), 403);

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($pickupRequest, $cleanReason) {
            $locked = PickupRequest::whereKey($pickupRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PickupRequest::STATUS_MENUNGGU) {
                throw ValidationException::withMessages([
                    'status' => "Penjemputan berstatus '{$locked->status}' tidak dapat ditolak. Penolakan hanya diizinkan dari status 'menunggu'.",
                ]);
            }

            $locked->status = PickupRequest::STATUS_DITOLAK;
            $locked->rejection_reason = $cleanReason;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Jadwalkan pengajuan penjemputan (hanya oleh Admin dan hanya dari status 'menunggu').
     */
    public function schedule(PickupRequest $pickupRequest, string $scheduledDate, int $officerId, User $admin): PickupRequest
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($pickupRequest, $scheduledDate, $officerId, $admin) {
            $locked = PickupRequest::whereKey($pickupRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PickupRequest::STATUS_MENUNGGU) {
                throw ValidationException::withMessages([
                    'status' => "Penjemputan berstatus '{$locked->status}' tidak dapat dijadwalkan ulang lewat aksi ini. Penjadwalan awal hanya diizinkan dari status 'menunggu'.",
                ]);
            }

            $officer = $this->resolveActiveOfficer($officerId);

            $locked->status = PickupRequest::STATUS_TERJADWAL;
            $locked->scheduled_date = $scheduledDate;
            $locked->officer_id = $officer->id;
            $locked->scheduled_by = $admin->id;
            $locked->scheduled_at = now();
            $locked->rejection_reason = null;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Ubah jadwal / petugas penjemputan (hanya oleh Admin dan hanya saat status 'terjadwal').
     */
    public function reschedule(PickupRequest $pickupRequest, string $scheduledDate, int $officerId, User $admin): PickupRequest
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($pickupRequest, $scheduledDate, $officerId, $admin) {
            $locked = PickupRequest::whereKey($pickupRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PickupRequest::STATUS_TERJADWAL) {
                throw ValidationException::withMessages([
                    'status' => "Perubahan jadwal tidak diizinkan untuk penjemputan berstatus '{$locked->status}'. Hanya penjemputan berstatus 'terjadwal' yang dapat diubah jadwalnya.",
                ]);
            }

            $officer = $this->resolveActiveOfficer($officerId);

            if ($locked->scheduled_date?->toDateString() !== $scheduledDate || (int) $locked->officer_id !== (int) $officer->id) {
                $locked->route_sequence = null;
            }

            $locked->scheduled_date = $scheduledDate;
            $locked->officer_id = $officer->id;
            $locked->scheduled_by = $admin->id;
            $locked->scheduled_at = now();
            $locked->save();

            return $locked;
        });
    }

    /**
     * Selesaikan tugas penjemputan oleh Petugas dan hasilkan setoran per baris — PBI-09.
     *
     * Berjalan dalam satu DB::transaction atomik: jika satu baris gagal,
     * seluruh Deposit & penambahan saldo dibatalkan dan status tetap 'terjadwal'.
     *
     * @param  list<array{waste_type_id: int|string, weight: float|string}>  $items
     * @return Collection<int, \App\Models\Deposit>
     */
    public function completePickup(PickupRequest $pickupRequest, array $items, User $officer): Collection
    {
        abort_unless($officer->isPetugas(), 403);

        $count = count($items);
        if ($count < 1 || $count > 10) {
            throw ValidationException::withMessages([
                'items' => 'Hasil penjemputan harus memuat antara 1 sampai 10 baris jenis sampah.',
            ]);
        }

        $wasteTypeIds = array_map(static fn (array $row) => (int) ($row['waste_type_id'] ?? 0), $items);
        if (count($wasteTypeIds) !== count(array_unique($wasteTypeIds))) {
            throw ValidationException::withMessages([
                'items' => 'Jenis sampah tidak boleh duplikat dalam satu penjemputan.',
            ]);
        }

        return DB::transaction(function () use ($pickupRequest, $items, $officer) {
            $locked = PickupRequest::whereKey($pickupRequest->id)->lockForUpdate()->firstOrFail();

            abort_unless((int) $locked->officer_id === (int) $officer->id, 403, 'Tugas penjemputan ini bukan milik Anda.');

            if ($locked->status !== PickupRequest::STATUS_TERJADWAL) {
                throw ValidationException::withMessages([
                    'status' => 'Penjemputan ini tidak dalam status terjadwal atau sudah diselesaikan.',
                ]);
            }

            $deposits = collect();

            foreach ($items as $item) {
                $deposit = $this->depositService->recordDeposit(
                    customerId: (int) $locked->customer_id,
                    wasteTypeId: (int) $item['waste_type_id'],
                    weight: $item['weight'],
                    officer: $officer,
                    pickupRequestId: $locked->id,
                );

                $deposits->push($deposit);
            }

            $locked->status = PickupRequest::STATUS_SELESAI;
            $locked->completed_at = now();
            $locked->save();

            return $deposits;
        });
    }

    private function resolveActiveOfficer(int $officerId): User
    {
        $officer = User::activeOfficers()->whereKey($officerId)->first();

        if ($officer === null) {
            throw ValidationException::withMessages([
                'officer_id' => 'Petugas tidak valid atau sedang nonaktif.',
            ]);
        }

        return $officer;
    }
}
