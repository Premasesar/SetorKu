@extends('layouts.ui-staff')

@section('title', 'Tugas Penjemputan ' . $pickupRequest->code)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #officer-pickup-map { height: 280px; width: 100%; border-radius: 0.375rem; }
</style>
@endpush

@section('content')
<x-page-hero
    :title="'Tugas Penjemputan ' . $pickupRequest->code"
    icon="bi-truck"
    :breadcrumbs="[
        ['label' => 'Tugas Penjemputan', 'url' => route('petugas.penjemputan.index')],
        ['label' => $pickupRequest->code],
    ]"
>
    <code>{{ $pickupRequest->code }}</code>
    <span class="badge {{ $pickupRequest->statusBadgeClass() }} align-middle ms-1">{{ $pickupRequest->statusLabel() }}</span>

    <x-slot:actions>
        <a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar tugas
        </a>
    </x-slot:actions>
</x-page-hero>

@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i> Pencatatan hasil gagal. Periksa kembali isian berikut:
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-lines-fill"></i> Informasi Nasabah & Lokasi
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kode</dt>
                    <dd class="col-sm-8"><code>{{ $pickupRequest->code }}</code></dd>

                    <dt class="col-sm-4">Tanggal Jadwal</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $pickupRequest->scheduled_date?->format('d-m-Y') ?? '-' }}</dd>

                    <dt class="col-sm-4">Nasabah</dt>
                    <dd class="col-sm-8">
                        {{ $pickupRequest->customer?->name }} (<code>{{ $pickupRequest->customer?->code }}</code>)
                        @if($pickupRequest->customer?->phone)
                            <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $pickupRequest->customer->phone }}</small>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Alamat</dt>
                    <dd class="col-sm-8">{!! nl2br(e($pickupRequest->address)) !!}</dd>

                    <dt class="col-sm-4">Koordinat</dt>
                    <dd class="col-sm-8">{{ $pickupRequest->latitude }}, {{ $pickupRequest->longitude }}</dd>

                    <dt class="col-sm-4">Catatan</dt>
                    <dd class="col-sm-8 mb-0">{{ $pickupRequest->notes ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold"><i class="bi bi-pin-map"></i> Peta & Navigasi</span>
                <div class="d-flex flex-wrap gap-2">
                    <a href="https://www.google.com/maps?q={{ $pickupRequest->latitude }},{{ $pickupRequest->longitude }}"
                       target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-geo-alt"></i> Google Maps
                    </a>
                    <a href="https://www.openstreetmap.org/?mlat={{ $pickupRequest->latitude }}&mlon={{ $pickupRequest->longitude }}#map=17/{{ $pickupRequest->latitude }}/{{ $pickupRequest->longitude }}"
                       target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-map"></i> OpenStreetMap
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="officer-pickup-map" class="sk-map border"></div>
            </div>
        </div>
    </div>
</div>

@if($pickupRequest->status === \App\Models\PickupRequest::STATUS_TERJADWAL)
    @php($oldItems = old('items', [['waste_type_id' => '', 'weight' => '']]))
    <div class="card shadow-sm border-success">
        <div class="card-header bg-success text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><i class="bi bi-clipboard2-check"></i> Catat Hasil Timbangan Penjemputan (1–10 Jenis Sampah)</span>
            <button type="button" id="btn-add-row" class="btn btn-sm btn-light text-success fw-semibold">
                <i class="bi bi-plus-circle"></i> Tambah Baris
            </button>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Setiap baris akan disimpan sebagai satu transaksi setoran terpisah yang terhubung ke <code>{{ $pickupRequest->code }}</code>. Jenis sampah tidak boleh dobel.
            </p>

            <form method="POST" action="{{ route('petugas.penjemputan.complete', $pickupRequest) }}" novalidate>
                @csrf

                <div id="items-container">
                    @foreach($oldItems as $idx => $row)
                        <div class="row g-2 align-items-center mb-2 pickup-item-row">
                            <div class="col-md-6">
                                <select name="items[{{ $idx }}][waste_type_id]" class="form-select" required>
                                    <option value="">-- Pilih Jenis Sampah Aktif --</option>
                                    @foreach($wasteTypes as $wt)
                                        <option value="{{ $wt->id }}" @selected((int) ($row['waste_type_id'] ?? 0) === $wt->id)>
                                            {{ $wt->name }} — Rp {{ number_format($wt->price_per_unit, 2, ',', '.') }} / {{ $wt->unit }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="number" step="0.001" min="0.001"
                                           name="items[{{ $idx }}][weight]"
                                           value="{{ $row['weight'] ?? '' }}"
                                           class="form-control" placeholder="Berat (mis. 2.500)" required>
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-outline-danger btn-remove-row w-100">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle"></i> Simpan Setoran & Selesaikan Penjemputan
                    </button>
                    <a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
@elseif($pickupRequest->deposits->isNotEmpty())
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-receipt"></i> Bukti Setoran Hasil Penjemputan ({{ $pickupRequest->deposits->count() }} setoran)
        </div>
        <div class="card-body">
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm align-middle mb-0 sk-table">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Setoran</th>
                            <th>Jenis Sampah</th>
                            <th class="text-end">Berat</th>
                            <th class="text-end">Harga Snapshot</th>
                            <th class="text-end">Total Nilai</th>
                            <th class="text-end">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pickupRequest->deposits as $deposit)
                            <tr>
                                <td><code>{{ $deposit->code }}</code></td>
                                <td>{{ $deposit->wasteType?->name }}</td>
                                <td class="text-end">{{ number_format($deposit->weight, 3, ',', '.') }} {{ $deposit->wasteType?->unit }}</td>
                                <td class="text-end">Rp {{ number_format($deposit->price_per_unit, 2, ',', '.') }}</td>
                                <td class="text-end fw-semibold text-success">Rp {{ number_format($deposit->total_value, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('petugas.setoran.show', $deposit) }}" class="btn btn-sm btn-outline-primary">Bukti</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sk-mobile-list d-md-none">
                @foreach($pickupRequest->deposits as $deposit)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <code>{{ $deposit->code }}</code>
                            <span class="fw-semibold text-success">Rp {{ number_format($deposit->total_value, 2, ',', '.') }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Jenis Sampah</span>
                            <span class="sk-mobile-card__value">{{ $deposit->wasteType?->name }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Berat</span>
                            <span class="sk-mobile-card__value">{{ number_format($deposit->weight, 3, ',', '.') }} {{ $deposit->wasteType?->unit }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Harga Snapshot</span>
                            <span class="sk-mobile-card__value">Rp {{ number_format($deposit->price_per_unit, 2, ',', '.') }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route('petugas.setoran.show', $deposit) }}" class="btn btn-sm btn-outline-primary w-100">Bukti</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar tugas</a>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = {{ (float) $pickupRequest->latitude }};
    const lng = {{ (float) $pickupRequest->longitude }};
    const map = L.map('officer-pickup-map').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);

    const container = document.getElementById('items-container');
    const addBtn = document.getElementById('btn-add-row');
    if (!container || !addBtn) return;

    let rowIndex = container.querySelectorAll('.pickup-item-row').length;

    function refreshButtons() {
        const rows = container.querySelectorAll('.pickup-item-row');
        rows.forEach(btnRow => {
            const rm = btnRow.querySelector('.btn-remove-row');
            if (rm) rm.disabled = rows.length <= 1;
        });
        addBtn.disabled = rows.length >= 10;
    }

    addBtn.addEventListener('click', function () {
        const rows = container.querySelectorAll('.pickup-item-row');
        if (rows.length >= 10) return;

        const firstRow = rows[0];
        const clone = firstRow.cloneNode(true);
        const select = clone.querySelector('select');
        const input = clone.querySelector('input');

        select.name = `items[${rowIndex}][waste_type_id]`;
        select.value = '';
        input.name = `items[${rowIndex}][weight]`;
        input.value = '';
        rowIndex++;

        container.appendChild(clone);
        refreshButtons();
    });

    container.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-row');
        if (!btn) return;
        const rows = container.querySelectorAll('.pickup-item-row');
        if (rows.length <= 1) return;
        btn.closest('.pickup-item-row').remove();
        refreshButtons();
    });

    refreshButtons();
});
</script>
@endpush
