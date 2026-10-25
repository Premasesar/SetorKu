@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">
        <i class="bi bi-speedometer2"></i> Dashboard Admin
    </h4>
    <p class="text-muted">Selamat datang, {{ auth()->user()->name }}!
        <span class="badge badge-admin">Admin</span>
    </p>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <i class="bi bi-people fs-1 text-success"></i>
                <h6 class="mt-2">Data Nasabah</h6>
                <p class="text-muted small">Kelola nasabah bank sampah</p>
                <a href="{{ route('admin.nasabah.index') }}" class="btn btn-sm btn-success">Buka</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <i class="bi bi-person-badge fs-1 text-info"></i>
                <h6 class="mt-2">Kelola Petugas</h6>
                <p class="text-muted small">Kelola akun petugas lapangan</p>
                <a href="{{ route('admin.petugas.index') }}" class="btn btn-sm btn-info text-white">Buka</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <i class="bi bi-tags fs-1 text-primary"></i>
                <h6 class="mt-2">Jenis & Harga Sampah</h6>
                <p class="text-muted small">Kelola tarif harga sampah</p>
                <a href="{{ route('admin.jenis-sampah.index') }}" class="btn btn-sm btn-primary">Buka</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <i class="bi bi-journal-text fs-1 text-warning"></i>
                <h6 class="mt-2">Laporan</h6>
                <p class="text-muted small">Rekap bulanan setoran</p>
                {{-- Link akan aktif setelah Sprint 2 --}}
            </div>
        </div>
    </div>
</div>
@endsection
