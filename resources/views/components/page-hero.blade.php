@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'breadcrumbs' => [],
    'badge' => null,
])

@php
    $iconClass = null;
    if ($icon !== null && $icon !== '') {
        $iconClass = str_starts_with($icon, 'bi ') || str_contains($icon, ' bi-')
            ? $icon
            : (str_starts_with($icon, 'bi-') ? 'bi ' . $icon : 'bi bi-' . $icon);
    }
@endphp

<section {{ $attributes->class(['sk-hero']) }}>
    <div class="sk-hero__inner">
        @if(!empty($breadcrumbs) || isset($meta) || $badge)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                @if(!empty($breadcrumbs))
                    <nav aria-label="Breadcrumb">
                        <ol class="sk-hero__breadcrumb">
                            @foreach($breadcrumbs as $item)
                                @php
                                    $itemLabel = is_array($item) ? ($item['label'] ?? '') : (string) $item;
                                    $itemUrl = is_array($item) ? ($item['url'] ?? null) : null;
                                @endphp
                                @if(!$loop->last && $itemUrl)
                                    <li><a href="{{ $itemUrl }}">{{ $itemLabel }}</a></li>
                                @elseif(!$loop->last)
                                    <li><span>{{ $itemLabel }}</span></li>
                                @else
                                    <li aria-current="page">{{ $itemLabel }}</li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                @endif

                @if(isset($meta))
                    <div>{{ $meta }}</div>
                @elseif($badge)
                    <span class="sk-hero-chip">{{ $badge }}</span>
                @endif
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1 class="sk-hero__title">
                    @if($iconClass)
                        <i class="{{ $iconClass }} me-1" aria-hidden="true"></i>
                    @endif
                    {{ $title }}
                </h1>
                @if($subtitle !== null && $subtitle !== '')
                    <p class="sk-hero__subtitle">{!! $subtitle !!}</p>
                @elseif(!$slot->isEmpty())
                    <div class="sk-hero__subtitle">{{ $slot }}</div>
                @endif
            </div>

            @if(isset($actions) && !$actions->isEmpty())
                <div class="sk-hero__actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</section>
