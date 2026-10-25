<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model WasteType (Jenis Sampah & Harga) — PBI-03 / FR-003.
 *
 * 'is_active' tidak dimasukkan ke $fillable agar status hanya berubah
 * melalui aksi eksplisit deactivate/activate oleh Admin.
 */
class WasteType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'price_per_unit',
    ];

    protected $attributes = [
        'unit' => 'kg',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }
}
