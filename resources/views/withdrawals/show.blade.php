@extends('layouts.ui-staff')

@section('title', 'Bukti Penarikan Saldo')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.penarikan' : 'admin.penarikan')

<nav aria-label="breadcrumb" class="d-print-none mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.index') }}">Riwayat Penarikan</a></li>
        <li class="breadcrumb-item active">Bukti {{ $withdrawal->code }}</li>
    </ol>
</nav>

<div class="d-print-none">
    <x-page-hero
        title="Bukti Penarikan Saldo"
        :subtitle="'Rincian transaksi penarikan saldo ' . $withdrawal->code . ' — ' . ($withdrawal->customer?->name ?? '-')"
        eyebrow="BUKTI TRANSAKSI PENARIKAN"
        icon="bi-receipt"
    >
        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Riwayat Penarikan
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer"></i> Cetak Bukti
        </button>
    </x-page-hero>
</div>

<div class="card sk-card shadow-sm mx-auto" style="max-width: 680px;">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold"><i class="bi bi-receipt me-1"></i> Bukti Penarikan Saldo — SetorKu</span>
        <code class="text-white fw-bold">{{ $withdrawal->code }}</code>
    </div>
    <div class="card-body p-4">
        <dl class="row mb-0 g-2">
            <dt class="col-sm-5 text-muted">Kode Transaksi</dt>
            <dd class="col-sm-7"><code class="fw-bold">{{ $withdrawal->code }}</code></dd>

            <dt class="col-sm-5 text-muted">Waktu Transaksi</dt>
            <dd class="col-sm-7">{{ $withdrawal->created_at?->format('d-m-Y H:i:s') }}</dd>

            <dt class="col-sm-5 text-muted">Nasabah</dt>
            <dd class="col-sm-7 fw-semibold">{{ $withdrawal->customer?->name }} (<code>{{ $withdrawal->customer?->code }}</code>)</dd>

            <dt class="col-sm-5 text-muted">Petugas Pencatat</dt>
            <dd class="col-sm-7">{{ $withdrawal->officer?->name }}</dd>

            <div class="col-12"><hr class="my-2"></div>

            <dt class="col-sm-5 fs-5 fw-bold">Nominal Penarikan</dt>
            <dd class="col-sm-7 fs-5 fw-bold text-danger">Rp {{ number_format($withdrawal->amount, 2, ',', '.') }}</dd>

            <dt class="col-sm-5 text-muted">Saldo Akhir Nasabah</dt>
            <dd class="col-sm-7 fw-semibold text-success mb-0">Rp {{ number_format($withdrawal->customer?->balance ?? 0, 2, ',', '.') }}</dd>
        </dl>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2 d-print-none py-3">
        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Riwayat Penarikan
        </a>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Cetak Bukti
            </button>
            @if($isPetugas)
                <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Catat Penarikan Baru
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
