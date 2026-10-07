@extends('layouts.app')

@section('title', 'Portal Nasabah')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">
        <i class="bi bi-person-circle"></i> Portal Nasabah
    </h4>
    <p class="text-muted">Selamat datang, {{ auth()->user()->name }}!
        <span class="badge badge-nasabah">Nasabah</span>
    </p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-wallet2 fs-1 text-success"></i>
                <h5 class="mt-2">Saldo Anda</h5>
                <h3 class="text-success fw-bold">Rp 0</h3>
                <p class="text-muted small">Saldo aktual akan muncul setelah fitur setoran aktif</p>
                {{-- Saldo dari Customer model akan ditampilkan setelah PBI-02 & PBI-04 --}}
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-secondary">
            <div class="card-body text-center">
                <i class="bi bi-clock-history fs-1 text-secondary"></i>
                <h6 class="mt-2">Riwayat Transaksi</h6>
                <p class="text-muted small">Akan tersedia setelah Sprint 2</p>
                {{-- Riwayat dari Deposit/Withdrawal akan ditampilkan setelah Sprint 2 --}}
            </div>
        </div>
    </div>
</div>
@endsection
