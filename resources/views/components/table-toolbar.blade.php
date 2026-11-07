@props([
    'action' => '',
    'method' => 'GET',
    'searchName' => 'q',
    'searchValue' => null,
    'placeholder' => 'Cari data...',
    'submitLabel' => 'Cari',
    'resetUrl' => null,
    'resetLabel' => 'Reset',
    'showReset' => true,
])

@php
    $currentSearch = $searchValue ?? request($searchName);
@endphp

<form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
      action="{{ $action }}"
      {{ $attributes->class(['sk-toolbar']) }}>
    @if(strtoupper($method) !== 'GET')
        @csrf
    @endif

    @if($searchName !== null && $searchName !== false)
        <div class="sk-toolbar__search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="text"
                   name="{{ $searchName }}"
                   value="{{ $currentSearch }}"
                   class="form-control"
                   placeholder="{{ $placeholder }}">
        </div>
    @endif

    @if(!$slot->isEmpty())
        <div class="sk-toolbar__filters">
            {{ $slot }}
        </div>
    @endif

    <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search me-1" aria-hidden="true"></i>{{ $submitLabel }}
        </button>

        @if($showReset && $resetUrl)
            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">
                {{ $resetLabel }}
            </a>
        @endif
    </div>
</form>
