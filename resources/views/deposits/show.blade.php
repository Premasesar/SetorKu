@extends('layouts.app')

@section('title', 'Bukti Setoran')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.setoran' : 'admin.setoran')

<nav aria-label="breadcrumb" class="d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.index') }}">Riwayat Setoran</a></li>
        <li class="breadcrumb-item active">Bukti {{ $deposit->code }}</li>
    </ol>
</nav>

<div class="card shadow-sm mx-auto" style="max-width: 680px;">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-receipt"></i> Bukti Setoran Sampah — SetorKu</span>
        <code>{{ $deposit->code }}</code>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Kode Transaksi</dt>
            <dd class="col-sm-7"><code>{{ $deposit->code }}</code></dd>

            <dt class="col-sm-5">Waktu Transaksi</dt>
            <dd class="col-sm-7">{{ $deposit->created_at?->format('d-m-Y H:i:s') }}</dd>

            <dt class="col-sm-5">Nasabah</dt>
            <dd class="col-sm-7">{{ $deposit->customer?->name }} (<code>{{ $deposit->customer?->code }}</code>)</dd>

            <dt class="col-sm-5">Petugas Pencatat</dt>
            <dd class="col-sm-7">{{ $deposit->officer?->name }}</dd>

            <hr class="my-2">

            <dt class="col-sm-5">Jenis Sampah</dt>
            <dd class="col-sm-7">{{ $deposit->wasteType?->name }}</dd>

            <dt class="col-sm-5">Berat</dt>
            <dd class="col-sm-7">{{ number_format($deposit->weight, 3, ',', '.') }} {{ $deposit->wasteType?->unit }}</dd>

            <dt class="col-sm-5">Harga Satuan (Snapshot)</dt>
            <dd class="col-sm-7">Rp {{ number_format($deposit->price_per_unit, 2, ',', '.') }} / {{ $deposit->wasteType?->unit }}</dd>

            <dt class="col-sm-5 fs-5">Total Nilai Setoran</dt>
            <dd class="col-sm-7 fs-5 fw-bold text-success mb-0">Rp {{ number_format($deposit->total_value, 2, ',', '.') }}</dd>
        </dl>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2 d-print-none">
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
