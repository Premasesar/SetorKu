@props([
    'label' => '',
    'value' => null,
    'hint' => null,
    'icon' => null,
    'variant' => 'default', // default | success | primary | danger | warning
    'accent' => false,
    'compact' => false,
])

@php
    $iconClass = null;
    if ($icon !== null && $icon !== '') {
        $iconClass = str_starts_with($icon, 'bi ') || str_contains($icon, ' bi-')
            ? $icon
            : (str_starts_with($icon, 'bi-') ? 'bi ' . $icon : 'bi bi-' . $icon);
    }

    $variantClass = match ($variant) {
        'success' => 'sk-stat-card--success',
        'primary' => 'sk-stat-card--primary',
        'danger'  => 'sk-stat-card--danger',
        'warning' => 'sk-stat-card--warning',
        default   => '',
    };
@endphp

<div {{ $attributes->class(['sk-stat-card', $variantClass, 'sk-stat-card--accent' => $accent]) }}>
    <div class="min-w-0">
        <div class="sk-stat-card__label">{{ $label }}</div>
        <div class="sk-stat-card__value {{ $compact ? 'sk-stat-card__value--sm' : '' }}">
            @if($value !== null)
                {{ $value }}
            @else
                {{ $slot }}
            @endif
        </div>
        @if($hint !== null && $hint !== '')
            <div class="sk-stat-card__hint">{{ $hint }}</div>
        @endif
    </div>

    @if($iconClass)
        <span class="sk-stat-card__icon" aria-hidden="true">
            <i class="{{ $iconClass }}"></i>
        </span>
    @endif
</div>
