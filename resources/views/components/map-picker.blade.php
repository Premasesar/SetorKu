{{--
    Komponen peta titik lokasi (Leaflet 1.9.4 + OpenStreetMap) — PBI-16 / PBI-17 / PBI-18.

    Mendukung dua mode:
    - mode="edit" (default): peta pemilih titik dengan pengaman keadaan TERKUNCI / AKTIF,
      tombol "Ubah Titik", "Gunakan Lokasi Saya", "Kembalikan Titik Semula", "Hapus Titik",
      serta input latitude/longitude readonly (7 digit desimal).
    - mode="view": peta baca-saja (zoom & geser peta aktif, pin tidak bisa digeser, klik
      peta diabaikan, tanpa input dan tanpa tombol aksi).

    Aset Leaflet dan public/js/map-picker.js dimuat sekali per halaman lewat @once.
    Dipasang di /daftar/lengkapi, /nasabah/profil (mode baca), dan /nasabah/profil/ubah.
--}}
@props([
    'id' => 'map-picker',
    'mode' => 'edit',
    'latName' => 'latitude',
    'lngName' => 'longitude',
    'latValue' => null,
    'lngValue' => null,
    'title' => 'Titik Lokasi Rumah (opsional)',
    'hint' => 'Klik pada peta atau geser pin ke lokasi rumah Anda.',
    'defaultLat' => -7.7970680,
    'defaultLng' => 110.3705290,
    'defaultZoom' => 13,
    'selectedZoom' => 17,
    'height' => '320px',
])

@php
    $isViewMode = ($mode === 'view');
    $latId = $latName;
    $lngId = $lngName;
    $latError = !$isViewMode && $errors->has($latName);
    $lngError = !$isViewMode && $errors->has($lngName);
    $hasInitialCoords = ($latValue !== null && trim((string) $latValue) !== '' && $lngValue !== null && trim((string) $lngValue) !== '');
@endphp

@once
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style data-setorku-map-picker-style>
    .map-picker-canvas {
        width: 100%;
        border-radius: 0.375rem;
        z-index: 0;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .map-picker-canvas.map-picker-canvas--active {
        border: 2px solid #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.2);
    }
</style>
@endonce

@if($isViewMode)
<div id="{{ $id }}"
     data-map-picker
     data-map-picker-mode="view"
     data-init-lat="{{ $latValue }}"
     data-init-lng="{{ $lngValue }}"
     data-selected-zoom="{{ $selectedZoom }}">
    @if($title)
        <div class="form-label fw-semibold mb-1">{{ $title }}</div>
    @endif
    @if($hint)
        <div class="form-text mt-0 mb-2">{{ $hint }}</div>
    @endif

    <div id="{{ $id }}-canvas"
         class="map-picker-canvas border"
         style="height: {{ $height }};"
         data-map-picker-canvas
         role="region"
         aria-label="Peta lokasi rumah"></div>

    <div class="small mt-2 text-muted" data-map-picker-status role="status" aria-live="polite"></div>
</div>
@else
<div class="mb-3"
     id="{{ $id }}"
     data-map-picker
     data-map-picker-mode="edit"
     data-lat-input="{{ $latId }}"
     data-lng-input="{{ $lngId }}"
     data-default-lat="{{ $defaultLat }}"
     data-default-lng="{{ $defaultLng }}"
     data-default-zoom="{{ $defaultZoom }}"
     data-selected-zoom="{{ $selectedZoom }}">

    <label class="form-label fw-semibold mb-1" for="{{ $id }}-canvas">{{ $title }}</label>
    <div class="form-text mt-0 mb-2">{{ $hint }}</div>

    <div id="{{ $id }}-canvas"
         class="map-picker-canvas border {{ $hasInitialCoords ? '' : 'map-picker-canvas--active' }}"
         style="height: {{ $height }};"
         data-map-picker-canvas
         role="application"
         aria-label="Peta pemilihan titik lokasi rumah"></div>

    <div class="small mt-2 text-muted {{ $hasInitialCoords ? '' : 'd-none' }}"
         data-map-picker-lock-hint>
        <i class="bi bi-lock-fill" aria-hidden="true"></i> Titik terkunci agar tidak berpindah tanpa sengaja.
    </div>

    <div class="d-flex flex-wrap gap-2 mt-2">
        <button type="button"
                class="btn btn-outline-primary btn-sm {{ $hasInitialCoords ? '' : 'd-none' }}"
                data-map-picker-unlock
                @if(!$hasInitialCoords) hidden @endif>
            <i class="bi bi-unlock" aria-hidden="true"></i> Ubah Titik
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" data-map-picker-locate>
            <i class="bi bi-crosshair" aria-hidden="true"></i> Gunakan Lokasi Saya
        </button>
        <button type="button"
                class="btn btn-outline-warning btn-sm d-none"
                data-map-picker-reset
                hidden>
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Kembalikan Titik Semula
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-map-picker-clear>
            <i class="bi bi-x-circle" aria-hidden="true"></i> Hapus Titik
        </button>
    </div>

    <div class="small mt-2 {{ $hasInitialCoords ? 'text-muted' : 'text-success fw-semibold' }}"
         data-map-picker-status
         role="status"
         aria-live="polite">{{ $hasInitialCoords ? '' : 'Mode ubah titik aktif: klik peta atau geser pin.' }}</div>

    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <label for="{{ $latId }}" class="form-label">Latitude <small class="text-muted">(opsional)</small></label>
            <input type="text"
                   id="{{ $latId }}"
                   name="{{ $latName }}"
                   inputmode="decimal"
                   readonly
                   placeholder="Pilih titik di peta"
                   class="form-control bg-light @if($latError) is-invalid @endif"
                   value="{{ $latValue }}">
            @error($latName)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="{{ $lngId }}" class="form-label">Longitude <small class="text-muted">(opsional)</small></label>
            <input type="text"
                   id="{{ $lngId }}"
                   name="{{ $lngName }}"
                   inputmode="decimal"
                   readonly
                   placeholder="Pilih titik di peta"
                   class="form-control bg-light @if($lngError) is-invalid @endif"
                   value="{{ $lngValue }}">
            @error($lngName)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
@endif

@once
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/map-picker.js') }}" data-setorku-map-picker-script defer></script>
@endonce
