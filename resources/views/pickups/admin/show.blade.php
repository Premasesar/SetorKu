@extends('layouts.app')

@section('title', 'Detail Penjemputan ' . $pickupRequest->code)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #admin-pickup-map { height: 280px; width: 100%; border-radius: 0.375rem; }
</style>
@endpush

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.penjemputan.index') }}">Kelola Penjemputan</a></li>
        <li class="breadcrumb-item active">{{ $pickupRequest->code }}</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-truck"></i> Penjemputan <code>{{ $pickupRequest->code }}</code>
        <span class="badge {{ $pickupRequest->statusBadgeClass() }} fs-6 align-middle">{{ $pickupRequest->statusLabel() }}</span>
    </h4>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($pickupRequest->status === \App\Models\PickupRequest::STATUS_DITOLAK && $pickupRequest->rejection_reason)
    <div class="alert alert-danger">
        <strong>Alasan Penolakan:</strong> {{ $pickupRequest->rejection_reason }}
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Informasi Pengajuan & Jadwal</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Kode</dt>
                    <dd class="col-sm-7"><code>{{ $pickupRequest->code }}</code></dd>

                    <dt class="col-sm-5">Nasabah</dt>
                    <dd class="col-sm-7">
                        {{ $pickupRequest->customer?->name }} (<code>{{ $pickupRequest->customer?->code }}</code>)
                        @if($pickupRequest->customer?->phone)
                            <br><small class="text-muted">{{ $pickupRequest->customer->phone }}</small>
                        @endif
                    </dd>

                    <dt class="col-sm-5">Tgl Pengajuan</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->requested_date?->format('d-m-Y') }}</dd>

                    <dt class="col-sm-5">Tgl Dijadwalkan</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->scheduled_date?->format('d-m-Y') ?? '-' }}</dd>

                    <dt class="col-sm-5">Petugas Penjemput</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->officer?->name ?? '-' }}</dd>

                    <dt class="col-sm-5">Dijadwalkan Oleh</dt>
                    <dd class="col-sm-7">
                        @if($pickupRequest->scheduler)
                            {{ $pickupRequest->scheduler->name }} ({{ $pickupRequest->scheduled_at?->format('d-m-Y H:i') }})
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-5">Waktu Selesai</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->completed_at?->format('d-m-Y H:i') ?? '-' }}</dd>

                    <dt class="col-sm-5">Alamat</dt>
                    <dd class="col-sm-7">{!! nl2br(e($pickupRequest->address)) !!}</dd>

                    <dt class="col-sm-5">Koordinat</dt>
                    <dd class="col-sm-7">{{ $pickupRequest->latitude }}, {{ $pickupRequest->longitude }}</dd>

                    <dt class="col-sm-5">Catatan Nasabah</dt>
                    <dd class="col-sm-7 mb-0">{{ $pickupRequest->notes ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Lokasi di Peta</div>
            <div class="card-body">
                <div id="admin-pickup-map" class="border"></div>
            </div>
        </div>
    </div>
</div>

@if($pickupRequest->status === \App\Models\PickupRequest::STATUS_MENUNGGU)
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-primary h-100">
                <div class="card-header bg-primary text-white fw-semibold">
                    <i class="bi bi-calendar-check"></i> Jadwalkan Penjemputan
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.penjemputan.schedule', $pickupRequest) }}" novalidate>
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="scheduled_date" class="form-label">Tanggal Jadwal <span class="text-danger">*</span></label>
                            <input type="date" id="scheduled_date" name="scheduled_date"
                                   min="{{ now()->toDateString() }}"
                                   class="form-control @error('scheduled_date') is-invalid @enderror"
                                   value="{{ old('scheduled_date', max($pickupRequest->requested_date?->toDateString() ?? now()->toDateString(), now()->toDateString())) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="officer_id" class="form-label">Pilih Petugas Aktif <span class="text-danger">*</span></label>
                            <select id="officer_id" name="officer_id" class="form-select @error('officer_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Petugas --</option>
                                @foreach($activeOfficers as $officer)
                                    <option value="{{ $officer->id }}" @selected((int) old('officer_id') === $officer->id)>
                                        {{ $officer->officer_code }} — {{ $officer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-check"></i> Simpan Jadwal</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-danger h-100">
                <div class="card-header bg-danger text-white fw-semibold">
                    <i class="bi bi-x-circle"></i> Tolak Pengajuan
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.penjemputan.reject', $pickupRequest) }}" novalidate>
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="1000"
                                      class="form-control @error('rejection_reason') is-invalid @enderror"
                                      placeholder="Jelaskan alasan penolakan kepada nasabah..." required>{{ old('rejection_reason') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Tolak Pengajuan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@elseif($pickupRequest->status === \App\Models\PickupRequest::STATUS_TERJADWAL)
    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning fw-semibold">
            <i class="bi bi-calendar2-week"></i> Ubah Jadwal / Petugas Penjemputan
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.penjemputan.reschedule', $pickupRequest) }}" class="row g-3 align-items-end" novalidate>
                @csrf
                @method('PATCH')

                <div class="col-md-4">
                    <label for="scheduled_date" class="form-label">Tanggal Jadwal Baru <span class="text-danger">*</span></label>
                    <input type="date" id="scheduled_date" name="scheduled_date"
                           min="{{ now()->toDateString() }}"
                           class="form-control @error('scheduled_date') is-invalid @enderror"
                           value="{{ old('scheduled_date', $pickupRequest->scheduled_date?->toDateString()) }}" required>
                </div>

                <div class="col-md-5">
                    <label for="officer_id" class="form-label">Petugas Aktif <span class="text-danger">*</span></label>
                    <select id="officer_id" name="officer_id" class="form-select @error('officer_id') is-invalid @enderror" required>
                        @foreach($activeOfficers as $officer)
                            <option value="{{ $officer->id }}" @selected((int) old('officer_id', $pickupRequest->officer_id) === $officer->id)>
                                {{ $officer->officer_code }} — {{ $officer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-warning w-100"><i class="bi bi-save"></i> Perbarui Jadwal</button>
                </div>
            </form>
            <div class="form-text mt-2">Catatan: Penyelesaian penjemputan hanya dapat dilakukan oleh Petugas di lapangan saat mencatat hasil timbangan.</div>
        </div>
    </div>
@endif

<a href="{{ route('admin.penjemputan.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar penjemputan</a>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = {{ (float) $pickupRequest->latitude }};
    const lng = {{ (float) $pickupRequest->longitude }};
    const map = L.map('admin-pickup-map').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
});
</script>
@endpush
