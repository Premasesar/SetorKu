@extends('layouts.app')

@section('title', 'Bukti Penarikan Saya')

@section('content')
<nav aria-label="breadcrumb" class="d-print-none">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('nasabah.riwayat.index') }}">Riwayat Transaksi</a></li>
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
            <dd class="col-sm-7 fs-5 fw-bold text-danger mb-0">Rp {{ number_format($withdrawal->amount, 2, ',', '.') }}</dd>
        </dl>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between d-print-none">
        <a href="{{ route('nasabah.riwayat.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Riwayat Transaksi
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-printer"></i> Cetak Bukti
        </button>
    </div>
</div>
@endsection
