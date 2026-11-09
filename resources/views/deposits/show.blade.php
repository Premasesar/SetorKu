@extends('layouts.ui-staff')

@section('title', 'Bukti Setoran')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.setoran' : 'admin.setoran')

<nav aria-label="breadcrumb" class="d-print-none mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.index') }}">Riwayat Setoran</a></li>
        <li class="breadcrumb-item active">Bukti {{ $deposit->code }}</li>
    </ol>
</nav>

<div class="d-print-none">
    <x-page-hero
        title="Bukti Setoran Sampah"
        :subtitle="'Rincian transaksi setoran ' . $deposit->code . ' — ' . ($deposit->customer?->name ?? '-')"
        icon="bi-receipt"
    >
        <x-slot:actions>
            <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Riwayat Setoran
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer"></i> Cetak Bukti
            </button>
        </x-slot:actions>
    </x-page-hero>
</div>

<div class="card sk-card shadow-sm mx-auto" style="max-width: 680px;">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold"><i class="bi bi-receipt me-1"></i> Bukti Setoran Sampah — SetorKu</span>
        <code class="text-white fw-bold">{{ $deposit->code }}</code>
    </div>
    <div class="card-body p-4">
        <dl class="row mb-0 g-2">
            <dt class="col-sm-5 text-muted">Kode Transaksi</dt>
            <dd class="col-sm-7">
                <code class="fw-bold">{{ $deposit->code }}</code>
                @if($deposit->pickupRequest)
                    <span class="badge bg-info text-dark ms-1">Dari penjemputan {{ $deposit->pickupRequest->code }}</span>
                @endif
            </dd>

            <dt class="col-sm-5 text-muted">Waktu Transaksi</dt>
            <dd class="col-sm-7">{{ $deposit->created_at?->format('d-m-Y H:i:s') }}</dd>

            <dt class="col-sm-5 text-muted">Nasabah</dt>
            <dd class="col-sm-7 fw-semibold">{{ $deposit->customer?->name }} (<code>{{ $deposit->customer?->code }}</code>)</dd>

            <dt class="col-sm-5 text-muted">Petugas Pencatat</dt>
            <dd class="col-sm-7">{{ $deposit->officer?->name }}</dd>

            <div class="col-12"><hr class="my-2"></div>

            <dt class="col-sm-5 text-muted">Jenis Sampah</dt>
            <dd class="col-sm-7 fw-semibold">{{ $deposit->wasteType?->name }}</dd>

            <dt class="col-sm-5 text-muted">Berat</dt>
            <dd class="col-sm-7 fw-semibold">{{ number_format($deposit->weight, 3, ',', '.') }} {{ $deposit->wasteType?->unit }}</dd>

            <dt class="col-sm-5 text-muted">Harga Satuan (Snapshot)</dt>
            <dd class="col-sm-7">Rp {{ number_format($deposit->price_per_unit, 2, ',', '.') }} / {{ $deposit->wasteType?->unit }}</dd>

            <dt class="col-sm-5 fs-5 fw-bold">Total Nilai Setoran</dt>
            <dd class="col-sm-7 fs-5 fw-bold text-success mb-0">Rp {{ number_format($deposit->total_value, 2, ',', '.') }}</dd>
        </dl>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2 d-print-none py-3">
        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Riwayat Setoran
        </a>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Cetak Bukti
            </button>
            @if($isPetugas)
                <a href="{{ route('petugas.setoran.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Catat Setoran Baru
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
