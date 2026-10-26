@props([
    'name' => 'password',
    'id' => null,
    'label' => null,
    'placeholder' => null,
    'required' => false,
    'requiredIndicator' => false,
    'autocomplete' => null,
    'prefixIcon' => null,
    'helpText' => null,
    'errorKey' => null,
])

@php
    $inputId = $id ?? $name;
    $validationKey = $errorKey ?? $name;
    $hasError = $errors->has($validationKey);
    $iconClass = null;

    if ($prefixIcon !== null && $prefixIcon !== '') {
        if (str_contains($prefixIcon, 'bi ')) {
            $iconClass = $prefixIcon;
        } elseif (str_starts_with($prefixIcon, 'bi-')) {
            $iconClass = 'bi ' . $prefixIcon;
        } else {
            $iconClass = 'bi bi-' . $prefixIcon;
        }
    }
@endphp

@if($label !== null && $label !== '')
    <label for="{{ $inputId }}" class="form-label">
        {{ $label }}
        @if($requiredIndicator)
            <span class="text-danger">*</span>
        @endif
    </label>
@endif

<div class="input-group @if($hasError) is-invalid @endif">
    @if($iconClass !== null)
        <span class="input-group-text"><i class="{{ $iconClass }}" aria-hidden="true"></i></span>
    @endif

    <input
        type="password"
        id="{{ $inputId }}"
        name="{{ $name }}"
        @if($placeholder !== null) placeholder="{{ $placeholder }}" @endif
        @if($autocomplete !== null) autocomplete="{{ $autocomplete }}" @endif
        @if($required) required @endif
        data-password-input
        {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
    >

    <button
        type="button"
        class="input-group-text password-toggle-btn"
        data-password-toggle="{{ $inputId }}"
        aria-label="Tampilkan password"
        aria-pressed="false"
    >
        <i class="bi bi-eye" aria-hidden="true"></i>
    </button>
</div>

@error($validationKey)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror

@if($helpText !== null && $helpText !== '')
    <div class="form-text">{{ $helpText }}</div>
@endif

@once
<style data-setorku-password-style>
    .password-toggle-btn {
        cursor: pointer;
    }
    .password-toggle-btn:hover {
        background-color: var(--bs-secondary-bg, #e9ecef);
    }
    .password-toggle-btn:focus-visible {
        position: relative;
        z-index: 5;
        outline: 0;
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>
<script data-setorku-password-script>
(function () {
    if (window.__setorkuPasswordToggleBound) {
        return;
    }
    window.__setorkuPasswordToggleBound = true;

    function syncToggleState(btn, input, isVisible) {
        input.type = isVisible ? 'text' : 'password';
        btn.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
        btn.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');

        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye', !isVisible);
            icon.classList.toggle('bi-eye-slash', isVisible);
        }
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-password-toggle]');
        if (!btn) {
            return;
        }

        var group = btn.closest('.input-group');
        var targetId = btn.getAttribute('data-password-toggle');
        var input = (group && group.querySelector('input[data-password-input]'))
            || (targetId ? document.getElementById(targetId) : null);

        if (!input) {
            return;
        }

        var shouldShow = input.type === 'password';
        syncToggleState(btn, input, shouldShow);

        input.focus({ preventScroll: true });
        try {
            var length = input.value.length;
            input.setSelectionRange(length, length);
        } catch (_) {}
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
            var group = btn.closest('.input-group');
            var targetId = btn.getAttribute('data-password-toggle');
            var input = (group && group.querySelector('input[data-password-input]'))
                || (targetId ? document.getElementById(targetId) : null);

            if (input) {
                syncToggleState(btn, input, false);
            }
        });
    });
})();
</script>
@endonce
