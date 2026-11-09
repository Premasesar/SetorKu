@extends('layouts.ui-staff')

@section('title', 'Riwayat Setoran')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.setoran' : 'admin.setoran')
@php($portalLabel = $isPetugas ? 'Portal Petugas' : 'Portal Admin')

<x-page-hero
    title="Riwayat Setoran"
    subtitle="Catatan seluruh transaksi setoran sampah nasabah beserta harga snapshot dan nilai tabungan."
    icon="bi-arrow-down-circle"
    :breadcrumbs="[$portalLabel, 'Riwayat Setoran']"
>
    @if($isPetugas)
        <x-slot:actions>
            <a href="{{ route('petugas.setoran.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Catat Setoran Baru
            </a>
        </x-slot:actions>
    @endif
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <x-table-toolbar
            :action="route($routePrefix . '.index')"
            search-name="q"
            :search-value="$search"
            placeholder="Cari nama/kode nasabah, kode setoran, atau jenis sampah..."
            submit-label="Cari"
            :reset-url="$search !== '' ? route($routePrefix . '.index') : null"
            reset-label="Reset"
            class="mb-3"
        />

        @if($deposits->isEmpty())
            <x-empty-state
                icon="bi-inbox"
                message="Belum ada riwayat setoran."
            />
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle sk-table">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Waktu</th>
                            <th>Nasabah</th>
                            <th>Jenis Sampah</th>
                            <th class="text-end">Berat</th>
                            <th class="text-end">Harga Snapshot</th>
                            <th class="text-end">Total Nilai</th>
                            <th>Petugas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deposits as $deposit)
                            <tr>
                                <td>
                                    <code>{{ $deposit->code }}</code>
                                    @if($deposit->pickupRequest)
                                        <br><span class="badge bg-info text-dark">Dari penjemputan {{ $deposit->pickupRequest->code }}</span>
                                    @endif
                                </td>
                                <td>{{ $deposit->created_at?->format('d-m-Y H:i') }}</td>
                                <td>
                                    {{ $deposit->customer?->name }}
                                    <br><small class="text-muted">{{ $deposit->customer?->code }}</small>
                                </td>
                                <td>{{ $deposit->wasteType?->name }}</td>
                                <td class="text-end">{{ number_format($deposit->weight, 3, ',', '.') }} {{ $deposit->wasteType?->unit }}</td>
                                <td class="text-end">Rp {{ number_format($deposit->price_per_unit, 2, ',', '.') }}</td>
                                <td class="text-end fw-semibold text-success">Rp {{ number_format($deposit->total_value, 2, ',', '.') }}</td>
                                <td>{{ $deposit->officer?->name }}</td>
                                <td class="text-end">
                                    <a href="{{ route($routePrefix . '.show', $deposit) }}" class="btn btn-sm btn-outline-primary">
                                        Bukti
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sk-mobile-list d-md-none mb-3">
                @foreach($deposits as $deposit)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <div>
                                <code>{{ $deposit->code }}</code>
                                @if($deposit->pickupRequest)
                                    <span class="badge bg-info text-dark ms-1">Dari penjemputan {{ $deposit->pickupRequest->code }}</span>
                                @endif
                            </div>
                            <span class="fw-semibold text-success">Rp {{ number_format($deposit->total_value, 2, ',', '.') }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Waktu</span>
                            <span class="sk-mobile-card__value">{{ $deposit->created_at?->format('d-m-Y H:i') }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Nasabah</span>
                            <span class="sk-mobile-card__value">
                                {{ $deposit->customer?->name }}
                                <small class="text-muted d-block">{{ $deposit->customer?->code }}</small>
                            </span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Jenis Sampah</span>
                            <span class="sk-mobile-card__value">{{ $deposit->wasteType?->name }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Berat</span>
                            <span class="sk-mobile-card__value">{{ number_format($deposit->weight, 3, ',', '.') }} {{ $deposit->wasteType?->unit }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Harga Snapshot</span>
                            <span class="sk-mobile-card__value">Rp {{ number_format($deposit->price_per_unit, 2, ',', '.') }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Petugas</span>
                            <span class="sk-mobile-card__value">{{ $deposit->officer?->name }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route($routePrefix . '.show', $deposit) }}" class="btn btn-sm btn-outline-primary w-100">
                                Bukti
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $deposits->firstItem() }}–{{ $deposits->lastItem() }} dari {{ $deposits->total() }} setoran
                </small>
                {{ $deposits->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
