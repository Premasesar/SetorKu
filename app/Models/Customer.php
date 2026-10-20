<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Customer (Nasabah) — PBI-02 / FR-002.
 *
 * 'balance' dan 'is_active' sengaja TIDAK dimasukkan ke $fillable sehingga
 * tidak dapat diisi lewat mass assignment dari form.
 * Relasi deposits/withdrawals akan ditambahkan pada PBI berikutnya.
 */
class Customer extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
    ];

    protected $attributes = [
        'balance' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
