<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder akun dummy SetorKu.
 *
 * Idempoten: aman dijalankan berulang (firstOrCreate) tanpa migrate:fresh.
 *
 * Akun yang dibuat:
 * - admin@banksampah.test / password (role: admin)
 * - petugas@banksampah.test / password (role: petugas)
 * - nasabah1@banksampah.test / password (role: nasabah + profil Customer)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Password otomatis di-hash oleh cast 'hashed' pada model User
        User::firstOrCreate(
            ['email' => 'admin@banksampah.test'],
            ['name' => 'Admin Bank Sampah', 'password' => 'password', 'role' => 'admin'],
        );

        User::firstOrCreate(
            ['email' => 'petugas@banksampah.test'],
            ['name' => 'Petugas Satu', 'password' => 'password', 'role' => 'petugas'],
        );

        $nasabah = User::firstOrCreate(
            ['email' => 'nasabah1@banksampah.test'],
            ['name' => 'Budi Santoso', 'password' => 'password', 'role' => 'nasabah'],
        );

        // PBI-02: profil nasabah untuk akun nasabah dummy (saldo default 0)
        $nasabah->customer()->firstOrCreate([], [
            'name' => 'Budi Santoso',
            'address' => 'Jl. Melati No. 5',
        ]);

        $this->command->info('Selesai. Login percobaan:');
        $this->command->info('  admin@banksampah.test / password');
        $this->command->info('  petugas@banksampah.test / password');
        $this->command->info('  nasabah1@banksampah.test / password');
    }
}
