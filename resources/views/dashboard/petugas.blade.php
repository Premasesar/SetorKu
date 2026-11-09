@extends('layouts.ui-staff')

@section('title', 'Dashboard Petugas')

@section('content')
<x-page-hero
    title="Dashboard Petugas"
    icon="bi-clipboard-check"
    :breadcrumbs="['Portal Petugas', 'Dashboard']"
>
    Selamat datang, {{ auth()->user()->name }}!
    <span class="badge badge-petugas ms-1">Petugas</span>

    <x-slot:actions>
        <a href="{{ route('petugas.setoran.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Catat Setoran
        </a>
        <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-warning">
            <i class="bi bi-arrow-up-circle"></i> Catat Penarikan
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-stat-card
            label="Tugas Penjemputan Hari Ini"
            :value="$stats['today_pickups']->count()"
            variant="primary"
            icon="bi-truck"
        />
    </div>
    <div class="col-md-4">
        <x-stat-card
            label="Setoran Dicatat Hari Ini"
            :value="$stats['today_deposits_count']"
            :hint="'Nilai: Rp ' . number_format($stats['today_deposits_value'], 2, ',', '.')"
            variant="success"
            icon="bi-arrow-down-circle"
        />
    </div>
    <div class="col-md-4">
        <x-stat-card
            label="Penarikan Dicatat Hari Ini"
            :value="$stats['today_withdrawals_count']"
            :hint="'Nominal: Rp ' . number_format($stats['today_withdrawals_amount'], 2, ',', '.')"
            variant="warning"
            icon="bi-arrow-up-circle"
        />
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold"><i class="bi bi-truck"></i> Daftar Tugas Penjemputan Hari Ini</span>
        <a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-sm btn-link">Semua Tugas Terjadwal</a>
    </div>
    <div class="card-body">
        @if($stats['today_pickups']->isEmpty())
            <x-empty-state
                icon="bi-truck"
                message="Tidak ada tugas penjemputan terjadwal untuk Anda hari ini."
                compact
            />
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0 sk-table">
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

            <div class="sk-mobile-list d-md-none">
                @foreach($stats['today_pickups'] as $pickup)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <code>{{ $pickup->code }}</code>
                            @if($pickup->route_sequence !== null)
                                <span class="badge bg-success">#{{ $pickup->route_sequence }}</span>
                            @endif
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Nasabah</span>
                            <span class="sk-mobile-card__value">{{ $pickup->customer?->name }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Alamat</span>
                            <span class="sk-mobile-card__value">{{ \Illuminate\Support\Str::limit($pickup->address, 55) }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route('petugas.penjemputan.show', $pickup) }}" class="btn btn-sm btn-success w-100">
                                Detail & Catat Hasil
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
