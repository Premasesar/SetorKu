<?php

namespace App\Models;

use App\Enums\PickupStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PickupRequest (Permintaan Penjemputan Sampah) — PBI-07 / FR-007.
 *
 * Kolom status, penjadwalan, petugas, dan penolakan sengaja TIDAK dimasukkan ke $fillable
 * agar tidak pernah dapat dimanipulasi dari input form browser.
 */
class PickupRequest extends Model
{
    use HasFactory;

    public const STATUS_MENUNGGU = PickupStatus::Menunggu->value;
    public const STATUS_DITOLAK = PickupStatus::Ditolak->value;
    public const STATUS_TERJADWAL = PickupStatus::Terjadwal->value;
    public const STATUS_SELESAI = PickupStatus::Selesai->value;

    public const STATUSES = [
        self::STATUS_MENUNGGU,
        self::STATUS_DITOLAK,
        self::STATUS_TERJADWAL,
        self::STATUS_SELESAI,
    ];

    protected $fillable = [
        'requested_date',
        'address',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $attributes = [
        'status' => self::STATUS_MENUNGGU,
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'scheduled_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function getCodeAttribute(): string
    {
        return sprintf('JMP-%04d', $this->id);
    }

    public function statusEnum(): PickupStatus
    {
        return PickupStatus::from($this->status);
    }

    public function statusLabel(): string
    {
        return PickupStatus::tryFrom($this->status)?->label() ?? ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return PickupStatus::tryFrom($this->status)?->badgeClass() ?? 'bg-secondary';
    }
}
