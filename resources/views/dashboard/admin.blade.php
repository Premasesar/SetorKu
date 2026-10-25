@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-speedometer2"></i> Dashboard Admin
        </h4>
        <p class="text-muted mb-0">
            Selamat datang, {{ auth()->user()->name }}!
            <span class="badge badge-admin">Admin</span>
        </p>
    </div>
    <a href="{{ route('admin.laporan.index') }}" class="btn btn-outline-success">
        <i class="bi bi-journal-text"></i> Buka Laporan Bulanan
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-success shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Nasabah Aktif</div>
                <h3 class="fw-bold text-success mt-1 mb-0">{{ number_format($stats['active_customers_count'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Saldo Seluruh Nasabah</div>
                <h4 class="fw-bold text-primary mt-1 mb-0">Rp {{ number_format($stats['total_customer_balance'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Setoran Bulan Ini ({{ $stats['month_deposits']['count'] }} trx)</div>
                <h5 class="fw-bold text-success mt-1 mb-1">Rp {{ number_format($stats['month_deposits']['total_value'], 2, ',', '.') }}</h5>
                <div class="small text-muted">Total Berat: {{ number_format($stats['month_deposits']['total_weight'], 3, ',', '.') }} kg</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Penarikan Bulan Ini ({{ $stats['month_withdrawals']['count'] }} trx)</div>
                <h5 class="fw-bold text-danger mt-1 mb-0">Rp {{ number_format($stats['month_withdrawals']['total_amount'], 2, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-truck"></i> Ringkasan Penjemputan per Status</div>
            <div class="card-body">
                <div class="row g-2 text-center">
                    @foreach(\App\Enums\PickupStatus::cases() as $case)
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <span class="badge {{ $case->badgeClass() }} mb-1">{{ $case->label() }}</span>
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
                    <i class="bi bi-calendar-event"></i> Penjemputan Terjadwal Hari Ini ({{ $stats['today_scheduled_pickups']->count() }})
                </span>
                <a href="{{ route('admin.penjemputan.index', ['status' => 'terjadwal', 'date' => now()->toDateString()]) }}" class="btn btn-sm btn-link">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if($stats['today_scheduled_pickups']->isEmpty())
                    <p class="text-muted text-center py-3 mb-0">Tidak ada penjemputan terjadwal untuk hari ini.</p>
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
