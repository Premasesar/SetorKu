@props([
    'status' => null,
    'label' => null,
    'type' => null, // pickup | account | transaction | auto
])

@php
    $rawValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $normalized = mb_strtolower(trim($rawValue));

    if ($status instanceof \App\Enums\PickupStatus) {
        $resolvedLabel = $label ?? $status->label();
    } else {
        $resolvedLabel = $label ?? match ($normalized) {
            'menunggu' => 'Menunggu',
            'terjadwal' => 'Terjadwal',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
            'aktif', 'active', '1', 'true' => 'Aktif',
            'nonaktif', 'inactive', '0', 'false' => 'Nonaktif',
            'setoran', 'deposit' => 'Setoran',
            'penarikan', 'withdrawal' => 'Penarikan',
            default => $rawValue !== '' ? $rawValue : '-',
        };
    }

    $pillClass = match ($normalized) {
        'menunggu' => 'sk-pill--warning',
        'terjadwal' => 'sk-pill--info',
        'selesai', 'aktif', 'active', '1', 'true' => 'sk-pill--success',
        'ditolak' => 'sk-pill--danger',
        'nonaktif', 'inactive', '0', 'false' => 'sk-pill--neutral',
        'setoran', 'deposit' => 'sk-pill--deposit',
        'penarikan', 'withdrawal' => 'sk-pill--withdrawal',
        'admin', 'petugas', 'nasabah' => 'sk-pill--role',
        default => 'sk-pill--neutral',
    };
@endphp

<span {{ $attributes->class(['sk-pill', $pillClass]) }}>{{ $resolvedLabel }}</span>
