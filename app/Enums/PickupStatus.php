<?php

namespace App\Enums;

/**
 * Status permintaan penjemputan sampah — PBI-07..PBI-09.
 *
 * Alur ketat: menunggu -> ditolak | terjadwal -> selesai.
 */
enum PickupStatus: string
{
    case Menunggu = 'menunggu';
    case Ditolak = 'ditolak';
    case Terjadwal = 'terjadwal';
    case Selesai = 'selesai';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Ditolak => 'Ditolak',
            self::Terjadwal => 'Terjadwal',
            self::Selesai => 'Selesai',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Menunggu => 'bg-warning text-dark',
            self::Ditolak => 'bg-danger',
            self::Terjadwal => 'bg-primary',
            self::Selesai => 'bg-success',
        };
    }
}
