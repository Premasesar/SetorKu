<?php

namespace App\Services;

use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Layanan aplikasi optimasi rute penjemputan (menghubungkan RouteOptimizer murni dengan database) — PBI-11.
 */
class RouteOptimizationService
{
    public function __construct(private readonly RouteOptimizer $optimizer)
    {
    }

    /**
     * Ambil konfigurasi titik depot Bank Sampah dari config/setorku.php.
     *
     * @return array{name: string, lat: float, lng: float}
     */
    public function depotConfig(): array
    {
        return [
            'name' => (string) config('setorku.bank_sampah.name', 'Bank Sampah SetorKu Yogyakarta'),
            'lat' => (float) config('setorku.bank_sampah.lat', -7.7970680),
            'lng' => (float) config('setorku.bank_sampah.lng', 110.3705290),
        ];
    }

    /**
     * Hitung rute optimal untuk tanggal dan petugas tertentu tanpa mengubah database.
     * Urutan awal didasarkan pada waktu permintaan masuk (created_at, lalu id).
     *
     * @return array<string, mixed>
     */
    public function calculateForOfficerAndDate(int $officerId, string $date): array
    {
        $pickups = PickupRequest::with('customer')
            ->where('status', PickupRequest::STATUS_TERJADWAL)
            ->whereDate('scheduled_date', $date)
            ->where('officer_id', $officerId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $stops = $this->mapPickupsToStops($pickups);

        return $this->optimizer->optimize($this->depotConfig(), $stops);
    }

    /**
     * Hitung ulang di backend dan simpan route_sequence (1..N) untuk penjemputan terjadwal.
     * Titik dengan koordinat tidak valid di-reset menjadi route_sequence = null.
     *
     * @return array<string, mixed>
     */
    public function saveForOfficerAndDate(int $officerId, string $date, User $admin): array
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($officerId, $date) {
            $pickups = PickupRequest::with('customer')
                ->where('status', PickupRequest::STATUS_TERJADWAL)
                ->whereDate('scheduled_date', $date)
                ->where('officer_id', $officerId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $stops = $this->mapPickupsToStops($pickups);
            $result = $this->optimizer->optimize($this->depotConfig(), $stops);

            $pickupsById = $pickups->keyBy('id');

            foreach ($result['ordered_stops'] as $orderedStop) {
                /** @var PickupRequest|null $model */
                $model = $pickupsById->get($orderedStop['id']);
                if ($model !== null) {
                    $model->route_sequence = (int) $orderedStop['sequence'];
                    $model->save();
                }
            }

            foreach ($result['excluded'] as $excludedItem) {
                $excludedId = $excludedItem['stop']['id'] ?? null;
                /** @var PickupRequest|null $model */
                $model = $excludedId !== null ? $pickupsById->get($excludedId) : null;
                if ($model !== null && $model->route_sequence !== null) {
                    $model->route_sequence = null;
                    $model->save();
                }
            }

            return $result;
        });
    }

    /**
     * Susun data peta rute read-only milik seorang petugas pada tanggal tertentu
     * sesuai urutan route_sequence (yang null diletakkan di akhir).
     *
     * @return array<string, mixed>
     */
    public function buildOfficerReadOnlyRoute(User $officer, string $date): array
    {
        $depot = $this->depotConfig();

        $pickups = PickupRequest::with('customer')
            ->where('status', PickupRequest::STATUS_TERJADWAL)
            ->whereDate('scheduled_date', $date)
            ->where('officer_id', $officer->id)
            ->orderByRaw('CASE WHEN route_sequence IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('route_sequence')
            ->orderBy('id')
            ->get();

        $partitioned = $this->optimizer->partitionStops($this->mapPickupsToStops($pickups));
        $validStops = $partitioned['valid'];

        $markers = [];
        $polyline = [];

        if ($validStops !== []) {
            $polyline[] = [$depot['lat'], $depot['lng']];
            $prevLat = $depot['lat'];
            $prevLng = $depot['lng'];
            $cumulative = 0.0;

            foreach ($validStops as $idx => $stop) {
                $leg = $this->optimizer->haversine($prevLat, $prevLng, $stop['lat'], $stop['lng']);
                $cumulative += $leg;

                $stop['display_number'] = $stop['route_sequence'] ?? ($idx + 1);
                $stop['leg_distance_km'] = $leg;
                $stop['cumulative_distance_km'] = $cumulative;

                $markers[] = $stop;
                $polyline[] = [$stop['lat'], $stop['lng']];

                $prevLat = $stop['lat'];
                $prevLng = $stop['lng'];
            }

            $returnLeg = $this->optimizer->haversine($prevLat, $prevLng, $depot['lat'], $depot['lng']);
            $totalClosed = $cumulative + $returnLeg;
            $polyline[] = [$depot['lat'], $depot['lng']];
        } else {
            $returnLeg = 0.0;
            $totalClosed = 0.0;
        }

        return [
            'date' => $date,
            'depot' => $depot,
            'pickups' => $pickups,
            'markers' => $markers,
            'excluded' => $partitioned['excluded'],
            'polyline' => $polyline,
            'return_leg_km' => $returnLeg,
            'total_distance_km' => $totalClosed,
        ];
    }

    /**
     * @param  Collection<int, PickupRequest>  $pickups
     * @return list<array<string, mixed>>
     */
    private function mapPickupsToStops(Collection $pickups): array
    {
        return $pickups->map(static fn (PickupRequest $p) => [
            'id' => $p->id,
            'code' => $p->code,
            'customer_name' => $p->customer?->name ?? '-',
            'customer_code' => $p->customer?->code ?? '-',
            'address' => $p->address,
            'latitude' => $p->getRawOriginal('latitude'),
            'longitude' => $p->getRawOriginal('longitude'),
            'route_sequence' => $p->route_sequence,
            'created_at' => $p->created_at?->format('d-m-Y H:i:s'),
        ])->values()->all();
    }
}
