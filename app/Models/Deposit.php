<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Deposit (Setoran Sampah) — PBI-04 / FR-004.
 */
class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'waste_type_id',
        'officer_id',
        'weight',
        'price_per_unit',
        'total_value',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'price_per_unit' => 'decimal:2',
            'total_value' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function wasteType(): BelongsTo
    {
        return $this->belongsTo(WasteType::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function getCodeAttribute(): string
    {
        return sprintf('STR-%04d', $this->id);
    }
}
