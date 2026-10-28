{{--
    Komponen pemilih titik lokasi (Leaflet 1.9.4 + OpenStreetMap) — PBI-16.

    Menampilkan peta yang bisa diklik dengan pin yang bisa digeser, tombol
    "Gunakan Lokasi Saya" dan "Hapus Titik", serta input latitude/longitude
    readonly yang diisi peta (7 digit desimal). Aset Leaflet dan
    public/js/map-picker.js dimuat sekali per halaman lewat @once sehingga
    komponen bekerja di layout mana pun (termasuk halaman tanpa @stack).

    Saat ini HANYA dipasang di halaman Lengkapi Pendaftaran (/daftar/lengkapi).
--}}
@props([
    'id' => 'map-picker',
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
    $latId = $latName;
    $lngId = $lngName;
    $latError = $errors->has($latName);
    $lngError = $errors->has($lngName);
@endphp

@once
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style data-setorku-map-picker-style>
    .map-picker-canvas {
        width: 100%;
        border-radius: 0.375rem;
        z-index: 0;
    }
</style>
@endonce

<div class="mb-3"
     id="{{ $id }}"
     data-map-picker
     data-lat-input="{{ $latId }}"
     data-lng-input="{{ $lngId }}"
     data-default-lat="{{ $defaultLat }}"
     data-default-lng="{{ $defaultLng }}"
     data-default-zoom="{{ $defaultZoom }}"
     data-selected-zoom="{{ $selectedZoom }}">

    <label class="form-label fw-semibold mb-1" for="{{ $id }}-canvas">{{ $title }}</label>
    <div class="form-text mt-0 mb-2">{{ $hint }}</div>

    <div id="{{ $id }}-canvas"
         class="map-picker-canvas border"
         style="height: {{ $height }};"
         data-map-picker-canvas
         role="application"
         aria-label="Peta pemilihan titik lokasi rumah"></div>

    <div class="d-flex flex-wrap gap-2 mt-2">
        <button type="button" class="btn btn-outline-success btn-sm" data-map-picker-locate>
            <i class="bi bi-crosshair" aria-hidden="true"></i> Gunakan Lokasi Saya
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-map-picker-clear>
            <i class="bi bi-x-circle" aria-hidden="true"></i> Hapus Titik
        </button>
    </div>

    <div class="small mt-2 text-muted" data-map-picker-status role="status" aria-live="polite"></div>

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

@once
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/map-picker.js') }}" data-setorku-map-picker-script defer></script>
@endonce
