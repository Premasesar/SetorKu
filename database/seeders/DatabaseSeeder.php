<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder akun dummy untuk testing PBI-01 (Authentication & Role).
 *
 * Akun yang dibuat:
 * - admin@banksampah.test / password (role: admin)
 * - petugas@banksampah.test / password (role: petugas)
 * - nasabah1@banksampah.test / password (role: nasabah)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Admin Bank Sampah',
            'email'    => 'admin@banksampah.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Petugas Satu',
            'email'    => 'petugas@banksampah.test',
            'password' => Hash::make('password'),
            'role'     => 'petugas',
        ]);

        User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'nasabah1@banksampah.test',
            'password' => Hash::make('password'),
            'role'     => 'nasabah',
        ]);

        $this->command->info('Selesai. Login percobaan:');
        $this->command->info('  admin@banksampah.test / password');
        $this->command->info('  petugas@banksampah.test / password');
        $this->command->info('  nasabah1@banksampah.test / password');
    }
}
