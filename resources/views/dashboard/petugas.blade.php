@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-clipboard-check"></i> Dashboard Petugas
        </h4>
        <p class="text-muted mb-0">
            Selamat datang, {{ auth()->user()->name }}!
            <span class="badge badge-petugas">Petugas</span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('petugas.setoran.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Catat Setoran
        </a>
        <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-warning">
            <i class="bi bi-arrow-up-circle"></i> Catat Penarikan
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-primary shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tugas Penjemputan Hari Ini</div>
                <h3 class="fw-bold text-primary mt-1 mb-0">{{ $stats['today_pickups']->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Setoran Dicatat Hari Ini</div>
                <h3 class="fw-bold text-success mt-1 mb-1">{{ $stats['today_deposits_count'] }}</h3>
                <div class="small text-muted">Nilai: Rp {{ number_format($stats['today_deposits_value'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Penarikan Dicatat Hari Ini</div>
                <h3 class="fw-bold text-danger mt-1 mb-1">{{ $stats['today_withdrawals_count'] }}</h3>
                <div class="small text-muted">Nominal: Rp {{ number_format($stats['today_withdrawals_amount'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-truck"></i> Daftar Tugas Penjemputan Hari Ini</span>
        <a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-sm btn-link">Semua Tugas Terjadwal</a>
    </div>
    <div class="card-body">
        @if($stats['today_pickups']->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Tidak ada tugas penjemputan terjadwal untuk Anda hari ini.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nasabah</th>
                            <th>Alamat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['today_pickups'] as $pickup)
                            <tr>
                                <td><code>{{ $pickup->code }}</code></td>
                                <td>{{ $pickup->customer?->name }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($pickup->address, 55) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('petugas.penjemputan.show', $pickup) }}" class="btn btn-sm btn-success">
                                        Detail & Catat Hasil
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
