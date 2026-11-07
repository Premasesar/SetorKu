@props([
    'name' => '',
    'id' => null,
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'prefixIcon' => null,
    'helpText' => null,
    'errorKey' => null,
    'rows' => 3,
])

@php
    $inputId = $id ?? $name;
    $validationKey = $errorKey ?? $name;
    $hasError = $validationKey !== '' && $errors->has($validationKey);
    $resolvedValue = old($validationKey, $value);

    $iconClass = null;
    if ($prefixIcon !== null && $prefixIcon !== '') {
        $iconClass = str_starts_with($prefixIcon, 'bi ') || str_contains($prefixIcon, ' bi-')
            ? $prefixIcon
            : (str_starts_with($prefixIcon, 'bi-') ? 'bi ' . $prefixIcon : 'bi bi-' . $prefixIcon);
    }
@endphp

<div class="mb-3">
    @if($label !== null && $label !== '')
        <label for="{{ $inputId }}" class="form-label">
            {{ $label }}
            @if($required && !str_contains($label, '*'))
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    @if(!$slot->isEmpty())
        {{ $slot }}
    @elseif($type === 'textarea')
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if($placeholder !== null) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($readonly) readonly @endif
            @if($disabled) disabled @endif
            {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
        >{{ $resolvedValue }}</textarea>
    @elseif($iconClass)
        <div class="input-group @if($hasError) is-invalid @endif">
            <span class="input-group-text"><i class="{{ $iconClass }}" aria-hidden="true"></i></span>
            <input
                type="{{ $type }}"
                id="{{ $inputId }}"
                name="{{ $name }}"
                value="{{ $resolvedValue }}"
                @if($placeholder !== null) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                @if($readonly) readonly @endif
                @if($disabled) disabled @endif
                {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
            >
        </div>
    @else
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ $resolvedValue }}"
            @if($placeholder !== null) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($readonly) readonly @endif
            @if($disabled) disabled @endif
            {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
        >
    @endif

    @if($hasError)
        <div class="invalid-feedback d-block">{{ $errors->first($validationKey) }}</div>
    @endif

    @if($helpText !== null && $helpText !== '')
        <div class="form-text">{{ $helpText }}</div>
    @endif
</div>
