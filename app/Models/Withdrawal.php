<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Withdrawal (Penarikan Saldo Nasabah) — PBI-05 / FR-005.
 */
class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'officer_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function getCodeAttribute(): string
    {
        return sprintf('TRK-%04d', $this->id);
    }
}
