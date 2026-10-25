@extends('layouts.app')

@section('title', 'Ajukan Penjemputan Sampah')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #pickup-map { height: 340px; width: 100%; border-radius: 0.375rem; }
</style>
@endpush

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('nasabah.penjemputan.index') }}">Penjemputan Saya</a></li>
        <li class="breadcrumb-item active">Ajukan Penjemputan</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-truck"></i> Ajukan Penjemputan Sampah</h4>

<div class="card shadow-sm">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> Pengajuan gagal dikirim. Periksa kembali isian berikut.
            </div>
        @endif

        <form method="POST" action="{{ route('nasabah.penjemputan.store') }}" novalidate>
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-5">
                    <label for="requested_date" class="form-label">Tanggal Penjemputan yang Diinginkan <span class="text-danger">*</span></label>
                    <input type="date" id="requested_date" name="requested_date"
                           min="{{ now()->toDateString() }}"
                           class="form-control @error('requested_date') is-invalid @enderror"
                           value="{{ old('requested_date', now()->toDateString()) }}" required>
                    @error('requested_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Alamat Penjemputan <span class="text-danger">*</span></label>
                <textarea id="address" name="address" rows="3" maxlength="1000"
                          class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $customer->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Pilih Titik Lokasi di Peta <span class="text-danger">*</span></label>
                <div class="form-text mb-2">Klik pada peta atau geser penanda untuk memilih titik koordinat penjemputan di Indonesia.</div>
                <div id="pickup-map" class="border"></div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                    <input type="text" id="latitude" name="latitude" inputmode="decimal"
                           placeholder="-7.7970680"
                           class="form-control @error('latitude') is-invalid @enderror"
                           value="{{ old('latitude', $customer->latitude) }}" required>
                    @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                    <input type="text" id="longitude" name="longitude" inputmode="decimal"
                           placeholder="110.3705290"
                           class="form-control @error('longitude') is-invalid @enderror"
                           value="{{ old('longitude', $customer->longitude) }}" required>
                    @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Catatan Perkiraan Jenis / Jumlah Sampah <small class="text-muted">(opsional)</small></label>
                <textarea id="notes" name="notes" rows="2" maxlength="1000"
                          placeholder="Contoh: Kardus ~5 kg dan botol plastik ~3 kg"
                          class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> Kirim Pengajuan</button>
                <a href="{{ route('nasabah.penjemputan.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    const defaultLat = -7.7970680;
    const defaultLng = 110.3705290;

    const initLat = parseFloat(latInput.value);
    const initLng = parseFloat(lngInput.value);
    const hasValidInit = !isNaN(initLat) && !isNaN(initLng) && initLat >= -11 && initLat <= 6 && initLng >= 95 && initLng <= 141;

    const centerLat = hasValidInit ? initLat : defaultLat;
    const centerLng = hasValidInit ? initLng : defaultLng;

    const map = L.map('pickup-map').setView([centerLat, centerLng], hasValidInit ? 15 : 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = hasValidInit ? L.marker([centerLat, centerLng], { draggable: true }).addTo(map) : null;

    function setCoords(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                setCoords(pos.lat, pos.lng);
            });
        } else {
            marker.setLatLng([lat, lng]);
        }
    }

    if (marker) {
        marker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            setCoords(pos.lat, pos.lng);
        });
    }

    map.on('click', function (e) {
        setCoords(e.latlng.lat, e.latlng.lng);
    });
});
</script>
@endpush
