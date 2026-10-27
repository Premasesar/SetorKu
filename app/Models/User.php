<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ------- Relasi -------

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    // ------- Scopes -------

    public function scopeOfficers(Builder $query): Builder
    {
        return $query->where('role', 'petugas');
    }

    /**
     * Pilihan petugas aktif (dipakai untuk penjadwalan penjemputan, dll).
     * Petugas nonaktif tidak muncul di daftar ini.
     */
    public function scopeActiveOfficers(Builder $query): Builder
    {
        return $query->where('role', 'petugas')->where('is_active', true);
    }

    // ------- Helper role & kode -------

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    public function isNasabah(): bool
    {
        return $this->role === 'nasabah';
    }

    public function getOfficerCodeAttribute(): string
    {
        return sprintf('PTG-%04d', $this->id);
    }

    /**
     * Periksa apakah akun aktif.
     * - Semua peran: memeriksa kolom users.is_active.
     * - Nasabah: memeriksa juga customers.is_active (bila profil tersedia).
     */
    public function isActiveAccount(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isNasabah()) {
            $customer = $this->relationLoaded('customer')
                ? $this->customer
                : $this->customer()->first();

            if ($customer !== null && ! $customer->is_active) {
                return false;
            }
        }

        return true;
    }
}
