<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('format_rupiah')) {
    /**
     * Format nominal uang ke format Rupiah Indonesia (contoh: Rp 9.750,00).
     */
    function format_rupiah(float|int|string|null $amount, int $decimals = 2, bool $withPrefix = true): string
    {
        $numeric = is_numeric($amount) ? (float) $amount : 0.0;
        $formatted = number_format($numeric, $decimals, ',', '.');

        return $withPrefix ? 'Rp ' . $formatted : $formatted;
    }
}

if (! function_exists('format_tanggal')) {
    /**
     * Format tanggal ke d-m-Y (contoh: 18-09-2026).
     */
    function format_tanggal(CarbonInterface|string|null $date, string $format = 'd-m-Y', string $fallback = '-'): string
    {
        if ($date === null || $date === '') {
            return $fallback;
        }

        if ($date instanceof CarbonInterface) {
            return $date->format($format);
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}

if (! function_exists('format_waktu')) {
    /**
     * Format tanggal & waktu ke d-m-Y H:i (contoh: 18-09-2026 14:30).
     */
    function format_waktu(CarbonInterface|string|null $date, string $format = 'd-m-Y H:i', string $fallback = '-'): string
    {
        return format_tanggal($date, $format, $fallback);
    }
}
