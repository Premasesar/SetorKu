<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder akun & nasabah dummy SetorKu.
 *
 * Idempoten: aman dijalankan berulang (updateOrCreate/firstOrCreate) tanpa migrate:fresh.
 *
 * Akun utama:
 * - admin@banksampah.test / password (role: admin)
 * - petugas@banksampah.test / password (role: petugas)
 * - nasabah1@banksampah.test s.d. nasabah11@banksampah.test / password (role: nasabah + koordinat Yogyakarta)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@banksampah.test'],
            ['name' => 'Admin Bank Sampah', 'password' => 'password', 'role' => 'admin'],
        );

        User::firstOrCreate(
            ['email' => 'petugas@banksampah.test'],
            ['name' => 'Petugas Satu', 'password' => 'password', 'role' => 'petugas'],
        );

        $dummyCustomers = [
            ['email' => 'nasabah1@banksampah.test',  'name' => 'Budi Santoso',      'address' => 'Jl. Malioboro No. 12, Gedongtengen, Yogyakarta',      'latitude' => -7.7925760, 'longitude' => 110.3658430],
            ['email' => 'nasabah2@banksampah.test',  'name' => 'Siti Aminah',       'address' => 'Jl. Kaliurang Km 5, Caturtunggal, Sleman',            'latitude' => -7.7630510, 'longitude' => 110.3789120],
            ['email' => 'nasabah3@banksampah.test',  'name' => 'Joko Prasetyo',     'address' => 'Jl. Prawirotaman No. 8, Mergangsan, Yogyakarta',      'latitude' => -7.8190240, 'longitude' => 110.3705180],
            ['email' => 'nasabah4@banksampah.test',  'name' => 'Dewi Lestari',      'address' => 'Jl. Gejayan No. 21, Depok, Sleman',                   'latitude' => -7.7745190, 'longitude' => 110.3894120],
            ['email' => 'nasabah5@banksampah.test',  'name' => 'Agus Setiawan',     'address' => 'Jl. Bantul Km 4, Sewon, Bantul',                      'latitude' => -7.8342100, 'longitude' => 110.3548900],
            ['email' => 'nasabah6@banksampah.test',  'name' => 'Rina Kusuma',       'address' => 'Jl. Kusumanegara No. 45, Umbulharjo, Yogyakarta',     'latitude' => -7.8021450, 'longitude' => 110.3912800],
            ['email' => 'nasabah7@banksampah.test',  'name' => 'Hendra Wijaya',     'address' => 'Jl. Godean Km 3, Banyuraden, Sleman',                 'latitude' => -7.7819300, 'longitude' => 110.3412500],
            ['email' => 'nasabah8@banksampah.test',  'name' => 'Sri Wahyuni',       'address' => 'Jl. Taman Siswa No. 30, Wirogunan, Yogyakarta',       'latitude' => -7.8076400, 'longitude' => 110.3778100],
            ['email' => 'nasabah9@banksampah.test',  'name' => 'Eko Nugroho',       'address' => 'Jl. Magelang Km 4, Sinduadi, Sleman',                 'latitude' => -7.7618900, 'longitude' => 110.3614200],
            ['email' => 'nasabah10@banksampah.test', 'name' => 'Ratna Sari',        'address' => 'Jl. Parangtritis No. 55, Brontokusuman, Yogyakarta',  'latitude' => -7.8214700, 'longitude' => 110.3681500],
            ['email' => 'nasabah11@banksampah.test', 'name' => 'Dian Permata',      'address' => 'Jl. Colombo No. 14, Samirono, Sleman',                'latitude' => -7.7768200, 'longitude' => 110.3816400],
        ];

        foreach ($dummyCustomers as $item) {
            $user = User::firstOrCreate(
                ['email' => $item['email']],
                ['name' => $item['name'], 'password' => 'password', 'role' => 'nasabah'],
            );

            $customer = $user->customer()->firstOrCreate(
                [],
                [
                    'name' => $item['name'],
                    'address' => $item['address'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                ],
            );

            // Lengkapi koordinat Yogyakarta jika nasabah lama belum punya koordinat
            if ($customer->latitude === null || $customer->longitude === null) {
                $customer->update([
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                ]);
            }
        }

        $this->call(WasteTypeSeeder::class);

        $this->command?->info('Selesai seed akun, 11 nasabah Yogyakarta, dan jenis sampah.');
    }
}
