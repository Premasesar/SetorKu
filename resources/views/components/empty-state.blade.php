@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'message' => 'Belum ada data.',
    'compact' => false,
])

@php
    $iconClass = null;
    if ($icon !== null && $icon !== '') {
        $iconClass = str_starts_with($icon, 'bi ') || str_contains($icon, ' bi-')
            ? $icon
            : (str_starts_with($icon, 'bi-') ? 'bi ' . $icon : 'bi bi-' . $icon);
    }
@endphp

<div {{ $attributes->class(['sk-empty', 'sk-empty--compact' => $compact]) }}>
    @if($iconClass)
        <div class="sk-empty__icon" aria-hidden="true">
            <i class="{{ $iconClass }}"></i>
        </div>
    @endif

    @if($title !== null && $title !== '')
        <div class="sk-empty__title">{{ $title }}</div>
    @endif

    <p class="sk-empty__text">{{ $message }}</p>

    @if(!$slot->isEmpty())
        <div class="mt-3">
            {{ $slot }}
        </div>
    @endif
</div>
