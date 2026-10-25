<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Services\PickupService;
use Illuminate\Database\Seeder;

/**
 * Seeder 12 Permintaan Penjemputan Yogyakarta — PBI-10.
 *
 * Idempoten: aman dijalankan berulang tanpa menduplikasi penjemputan atau setoran.
 * Distribusi:
 * - 8 'terjadwal' pada hari ini (4 ditugaskan ke Petugas Satu, 4 ke Petugas Dua)
 * - 2 'menunggu' (untuk besok)
 * - 1 'ditolak' (dengan alasan)
 * - 1 'selesai' (beserta setoran hasil penjemputan melalui PickupService)
 */
class PickupRequestSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        $officers = User::activeOfficers()->orderBy('id')->limit(2)->get();
        $customers = Customer::active()->orderBy('id')->get();

        if ($admin === null || $officers->count() < 2 || $customers->isEmpty()) {
            return;
        }

        $officer1 = $officers[0];
        $officer2 = $officers[1];
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $seeds = [
            // 1..4: Terjadwal hari ini -> Petugas Satu
            ['tag' => '[SEED-JMP-01]', 'cust_idx' => 0,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer1, 'address' => 'Jl. Malioboro No. 12, Gedongtengen, Yogyakarta',     'lat' => -7.7925760, 'lng' => 110.3658430, 'desc' => 'Botol plastik PET ~4 kg & kardus ~3 kg'],
            ['tag' => '[SEED-JMP-02]', 'cust_idx' => 1,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer1, 'address' => 'Jl. Kaliurang Km 5, Caturtunggal, Sleman',           'lat' => -7.7630510, 'lng' => 110.3789120, 'desc' => 'Kertas arsip ~6 kg'],
            ['tag' => '[SEED-JMP-03]', 'cust_idx' => 3,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer1, 'address' => 'Jl. Gejayan No. 21, Depok, Sleman',                  'lat' => -7.7745190, 'lng' => 110.3894120, 'desc' => 'Kardus kemasan ~5 kg & kaleng ~2 kg'],
            ['tag' => '[SEED-JMP-04]', 'cust_idx' => 10, 'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer1, 'address' => 'Jl. Colombo No. 14, Samirono, Sleman',               'lat' => -7.7768200, 'lng' => 110.3816400, 'desc' => 'Plastik PET ~3.5 kg'],

            // 5..8: Terjadwal hari ini -> Petugas Dua
            ['tag' => '[SEED-JMP-05]', 'cust_idx' => 2,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer2, 'address' => 'Jl. Prawirotaman No. 8, Mergangsan, Yogyakarta',     'lat' => -7.8190240, 'lng' => 110.3705180, 'desc' => 'Botol kaca ~4 kg & kardus ~3 kg'],
            ['tag' => '[SEED-JMP-06]', 'cust_idx' => 4,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer2, 'address' => 'Jl. Bantul Km 4, Sewon, Bantul',                     'lat' => -7.8342100, 'lng' => 110.3548900, 'desc' => 'Plastik PET ~5 kg'],
            ['tag' => '[SEED-JMP-07]', 'cust_idx' => 5,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer2, 'address' => 'Jl. Kusumanegara No. 45, Umbulharjo, Yogyakarta',    'lat' => -7.8021450, 'lng' => 110.3912800, 'desc' => 'Kaleng minuman ~2.5 kg & kertas ~4 kg'],
            ['tag' => '[SEED-JMP-08]', 'cust_idx' => 7,  'status' => PickupRequest::STATUS_TERJADWAL, 'officer' => $officer2, 'address' => 'Jl. Taman Siswa No. 30, Wirogunan, Yogyakarta',      'lat' => -7.8076400, 'lng' => 110.3778100, 'desc' => 'Kardus toko ~7 kg'],

            // 9..10: Menunggu konfirmasi
            ['tag' => '[SEED-JMP-09]', 'cust_idx' => 6,  'status' => PickupRequest::STATUS_MENUNGGU,  'officer' => null,      'address' => 'Jl. Godean Km 3, Banyuraden, Sleman',                'lat' => -7.7819300, 'lng' => 110.3412500, 'desc' => 'Plastik PET ~3 kg & kertas ~2 kg'],
            ['tag' => '[SEED-JMP-10]', 'cust_idx' => 8,  'status' => PickupRequest::STATUS_MENUNGGU,  'officer' => null,      'address' => 'Jl. Magelang Km 4, Sinduadi, Sleman',                'lat' => -7.7618900, 'lng' => 110.3614200, 'desc' => 'Kardus pindahan ~8 kg'],

            // 11: Ditolak
            ['tag' => '[SEED-JMP-11]', 'cust_idx' => 9,  'status' => PickupRequest::STATUS_DITOLAK,   'officer' => null,      'address' => 'Jl. Parangtritis No. 55, Brontokusuman, Yogyakarta', 'lat' => -7.8214700, 'lng' => 110.3681500, 'desc' => 'Sampah campuran < 1 kg'],

            // 12: Selesai (menghasilkan setoran)
            ['tag' => '[SEED-JMP-12]', 'cust_idx' => 0,  'status' => PickupRequest::STATUS_SELESAI,   'officer' => $officer1, 'address' => 'Jl. Mondorakan No. 17, Kotagede, Yogyakarta',        'lat' => -7.8278900, 'lng' => 110.3997200, 'desc' => 'Plastik PET & Kardus siap timbang'],
        ];

        $pickupService = app(PickupService::class);
        $petType = WasteType::where('name', 'Plastik PET')->first();
        $kardusType = WasteType::where('name', 'Kardus')->first();

        foreach ($seeds as $item) {
            $customer = $customers[$item['cust_idx'] % $customers->count()];
            $notesText = "{$item['tag']} {$item['desc']}";

            $pickup = PickupRequest::where('notes', 'like', "{$item['tag']}%")->first();

            if ($pickup === null) {
                $pickup = new PickupRequest([
                    'requested_date' => $item['status'] === PickupRequest::STATUS_MENUNGGU ? $tomorrow : $today,
                    'address' => $item['address'],
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'notes' => $notesText,
                ]);
                $pickup->customer_id = $customer->id;
                $pickup->status = PickupRequest::STATUS_MENUNGGU;
                $pickup->save();
            }

            if ($item['status'] === PickupRequest::STATUS_TERJADWAL) {
                // Pastikan tetap terjadwal pada HARI INI ketika seeder dijalankan ulang
                if ($pickup->status !== PickupRequest::STATUS_SELESAI) {
                    $pickup->requested_date = $today;
                    $pickup->scheduled_date = $today;
                    $pickup->status = PickupRequest::STATUS_TERJADWAL;
                    $pickup->officer_id = $item['officer']->id;
                    $pickup->scheduled_by = $admin->id;
                    $pickup->scheduled_at = $pickup->scheduled_at ?? now();
                    $pickup->save();
                }
            } elseif ($item['status'] === PickupRequest::STATUS_MENUNGGU) {
                if ($pickup->status === PickupRequest::STATUS_MENUNGGU) {
                    $pickup->requested_date = $tomorrow;
                    $pickup->save();
                }
            } elseif ($item['status'] === PickupRequest::STATUS_DITOLAK && $pickup->status === PickupRequest::STATUS_MENUNGGU) {
                $pickupService->reject($pickup, 'Volume di bawah batas minimum penjemputan armada.', $admin);
            } elseif ($item['status'] === PickupRequest::STATUS_SELESAI && $pickup->status === PickupRequest::STATUS_MENUNGGU) {
                $scheduled = $pickupService->schedule($pickup, $today, $item['officer']->id, $admin);
                if ($petType !== null && $kardusType !== null) {
                    $pickupService->completePickup($scheduled, [
                        ['waste_type_id' => $petType->id, 'weight' => 2.500],
                        ['waste_type_id' => $kardusType->id, 'weight' => 3.000],
                    ], $item['officer']);
                }
            }
        }
    }
}
