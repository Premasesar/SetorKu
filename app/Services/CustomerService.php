<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Layanan pembuatan akun User (role nasabah) beserta profil Customer secara konsisten.
 *
 * Dipakai bersama oleh pengelolaan nasabah (Admin/Petugas) dan pendaftaran mandiri via Google (PBI-15).
 * Saldo awal selalu 0, status aktif true, kode NSB-xxxx otomatis dari accessor.
 */
class CustomerService
{
    /**
     * Buat User ber-role 'nasabah' beserta baris Customer dalam satu transaksi.
     *
     * @param  array{name: string, email: string, address: string, phone?: string|null, latitude?: mixed, longitude?: mixed}  $data
     */
    public function createCustomerAccount(
        array $data,
        ?string $password = null,
        ?string $googleId = null,
        bool $markEmailVerified = false,
    ): Customer {
        return DB::transaction(function () use ($data, $password, $googleId, $markEmailVerified) {
            $email = mb_strtolower(trim((string) $data['email']));

            $user = new User([
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => ($password !== null && $password !== '') ? $password : null,
                'role' => 'nasabah',
            ]);

            if ($googleId !== null && $googleId !== '') {
                $user->google_id = $googleId;
            }

            if ($markEmailVerified) {
                $user->email_verified_at = now();
            }

            $user->is_active = true;
            $user->save();

            return $user->customer()->create([
                'name' => $data['name'],
                'address' => $data['address'],
                'phone' => $data['phone'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);
        });
    }
}
