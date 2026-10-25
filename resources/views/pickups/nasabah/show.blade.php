@extends('layouts.app')

@section('title', 'Detail Penjemputan ' . $pickupRequest->code)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #detail-map { height: 280px; width: 100%; border-radius: 0.375rem; }
</style>
@endpush

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('nasabah.penjemputan.index') }}">Penjemputan Saya</a></li>
        <li class="breadcrumb-item active">{{ $pickupRequest->code }}</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-truck"></i> Penjemputan <code>{{ $pickupRequest->code }}</code>
        <span class="badge {{ $pickupRequest->statusBadgeClass() }} fs-6 align-middle">{{ $pickupRequest->statusLabel() }}</span>
    </h4>
</div>

@if($pickupRequest->status === \App\Models\PickupRequest::STATUS_DITOLAK && $pickupRequest->rejection_reason)
    <div class="alert alert-danger">
        <strong>Alasan Penolakan:</strong> {{ $pickupRequest->rejection_reason }}
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Informasi Penjemputan</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Kode Pengajuan</dt>
                    <dd class="col-sm-7"><code>{{ $pickupRequest->code }}</code></dd>

                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7"><span class="badge {{ $pickupRequest->statusBadgeClass() }}">{{ $pickupRequest->statusLabel() }}</span></dd>

                    <dt class="col-sm-5">Tanggal Diajukan</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->requested_date?->format('d-m-Y') }}</dd>

                    <dt class="col-sm-5">Jadwal Penjemputan</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->scheduled_date?->format('d-m-Y') ?? '-' }}</dd>

                    <dt class="col-sm-5">Petugas Penjemput</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->officer?->name ?? '-' }}</dd>

                    <dt class="col-sm-5">Waktu Selesai</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->completed_at?->format('d-m-Y H:i') ?? '-' }}</dd>

                    <dt class="col-sm-5">Alamat</dt>
                    <dd class="col-sm-7">{!! nl2br(e($pickupRequest->address)) !!}</dd>

                    <dt class="col-sm-5">Koordinat</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->latitude }}, {{ $pickupRequest->longitude }}</dd>

                    <dt class="col-sm-5">Catatan Sampah</dt>
                    <dd class="col-sm-7 mb-0">{{ $pickupRequest->notes ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Lokasi Penjemputan</div>
            <div class="card-body">
                <div id="detail-map" class="border"></div>
            </div>
        </div>
    </div>
</div>

@if($pickupRequest->deposits->isNotEmpty())
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-receipt"></i> Daftar Bukti Setoran Hasil Penjemputan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
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
                                    <a href="{{ route('nasabah.riwayat.deposit', $deposit) }}" class="btn btn-sm btn-outline-primary">Bukti</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<a href="{{ route('nasabah.penjemputan.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar penjemputan</a>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = {{ (float) $pickupRequest->latitude }};
    const lng = {{ (float) $pickupRequest->longitude }};
    const map = L.map('detail-map').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
});
</script>
@endpush
