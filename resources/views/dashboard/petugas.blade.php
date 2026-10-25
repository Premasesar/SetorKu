@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">
        <i class="bi bi-clipboard-check"></i> Dashboard Petugas
    </h4>
    <p class="text-muted">Selamat datang, {{ auth()->user()->name }}!
        <span class="badge badge-petugas">Petugas</span>
    </p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-plus-circle fs-1 text-success"></i>
                <h6 class="mt-2">Buat Setoran Baru</h6>
                <p class="text-muted small">Catat setoran sampah nasabah</p>
                <a href="{{ route('petugas.setoran.create') }}" class="btn btn-sm btn-success">Catat Setoran</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-arrow-up-circle fs-1 text-warning"></i>
                <h6 class="mt-2">Penarikan Saldo</h6>
                <p class="text-muted small">Catat penarikan saldo nasabah</p>
                <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-sm btn-warning">Catat Penarikan</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="bi bi-search fs-1 text-info"></i>
                <h6 class="mt-2">Cari Nasabah</h6>
                <p class="text-muted small">Cari data nasabah terdaftar</p>
                <a href="{{ route('petugas.nasabah.index') }}" class="btn btn-sm btn-info text-white">Buka</a>
            </div>
        </div>
    </div>
</div>
@endsection
