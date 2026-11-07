@extends('layouts.ui-staff')

@section('title', 'Dashboard Admin')

@section('hero')
    <x-page-hero
        title="Dashboard Admin"
        icon="bi-speedometer2"
        :breadcrumbs="[['label' => 'Portal Admin'], ['label' => 'Dashboard']]"
    >
        Selamat datang, {{ auth()->user()->name }}!
        <span class="badge badge-admin ms-1">Admin</span>

        <x-slot:actions>
            <a href="{{ route('admin.laporan.index') }}" class="btn btn-outline-success">
                <i class="bi bi-journal-text"></i> Buka Laporan Bulanan
            </a>
        </x-slot:actions>
    </x-page-hero>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-stat-card
            label="Nasabah Aktif"
            :value="number_format($stats['active_customers_count'], 0, ',', '.')"
            icon="bi-people"
            variant="success"
            :accent="true"
        />
    </div>
    <div class="col-md-3">
        <x-stat-card
            label="Total Saldo Seluruh Nasabah"
            :value="'Rp ' . number_format($stats['total_customer_balance'], 2, ',', '.')"
            icon="bi-wallet2"
            variant="primary"
            :accent="true"
            :compact="true"
        />
    </div>
    <div class="col-md-3">
        <x-stat-card
            :label="'Setoran Bulan Ini (' . $stats['month_deposits']['count'] . ' trx)'"
            :value="'Rp ' . number_format($stats['month_deposits']['total_value'], 2, ',', '.')"
            :hint="'Total Berat: ' . number_format($stats['month_deposits']['total_weight'], 3, ',', '.') . ' kg'"
            icon="bi-arrow-down-circle"
            variant="success"
            :compact="true"
        />
    </div>
    <div class="col-md-3">
        <x-stat-card
            :label="'Penarikan Bulan Ini (' . $stats['month_withdrawals']['count'] . ' trx)'"
            :value="'Rp ' . number_format($stats['month_withdrawals']['total_amount'], 2, ',', '.')"
            icon="bi-arrow-up-circle"
            variant="danger"
            :compact="true"
        />
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-truck text-success me-1"></i> Ringkasan Penjemputan per Status</div>
            <div class="card-body">
                <div class="row g-2 text-center">
                    @foreach(\App\Enums\PickupStatus::cases() as $case)
                        <div class="col-6">
                            <div class="sk-status-tile">
                                <div class="mb-1">
                                    <x-status-pill :status="$case" :label="$case->label()" />
                                </div>
                                <h4 class="fw-bold mb-0">{{ $stats['pickups_by_status'][$case->value] ?? 0 }}</h4>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-calendar-event text-success me-1"></i> Penjemputan Terjadwal Hari Ini ({{ $stats['today_scheduled_pickups']->count() }})
                </span>
                <a href="{{ route('admin.penjemputan.index', ['status' => 'terjadwal', 'date' => now()->toDateString()]) }}" class="btn btn-sm btn-link">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if($stats['today_scheduled_pickups']->isEmpty())
                    <x-empty-state
                        icon="bi-calendar-check"
                        message="Tidak ada penjemputan terjadwal untuk hari ini."
                        :compact="true"
                    />
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nasabah</th>
                                    <th>Petugas</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['today_scheduled_pickups'] as $pickup)
                                    <tr>
                                        <td><code>{{ $pickup->code }}</code></td>
                                        <td>{{ $pickup->customer?->name }}</td>
                                        <td>{{ $pickup->officer?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.penjemputan.show', $pickup) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
