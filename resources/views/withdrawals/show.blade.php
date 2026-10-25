@extends('layouts.app')

@section('title', 'Bukti Penarikan Saldo')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.penarikan' : 'admin.penarikan')

<nav aria-label="breadcrumb" class="d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.index') }}">Riwayat Penarikan</a></li>
        <li class="breadcrumb-item active">Bukti {{ $withdrawal->code }}</li>
    </ol>
</nav>

<div class="card shadow-sm mx-auto" style="max-width: 680px;">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-receipt"></i> Bukti Penarikan Saldo — SetorKu</span>
        <code>{{ $withdrawal->code }}</code>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Kode Transaksi</dt>
            <dd class="col-sm-7"><code>{{ $withdrawal->code }}</code></dd>

            <dt class="col-sm-5">Waktu Transaksi</dt>
            <dd class="col-sm-7">{{ $withdrawal->created_at?->format('d-m-Y H:i:s') }}</dd>

            <dt class="col-sm-5">Nasabah</dt>
            <dd class="col-sm-7">{{ $withdrawal->customer?->name }} (<code>{{ $withdrawal->customer?->code }}</code>)</dd>

            <dt class="col-sm-5">Petugas Pencatat</dt>
            <dd class="col-sm-7">{{ $withdrawal->officer?->name }}</dd>

            <hr class="my-2">

            <dt class="col-sm-5 fs-5">Nominal Penarikan</dt>
            <dd class="col-sm-7 fs-5 fw-bold text-danger">Rp {{ number_format($withdrawal->amount, 2, ',', '.') }}</dd>

            <dt class="col-sm-5">Saldo Akhir Nasabah</dt>
            <dd class="col-sm-7 fw-semibold text-success mb-0">Rp {{ number_format($withdrawal->customer?->balance ?? 0, 2, ',', '.') }}</dd>
        </dl>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2 d-print-none">
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
