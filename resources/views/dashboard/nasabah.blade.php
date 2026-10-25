@extends('layouts.app')

@section('title', 'Portal Nasabah')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-person-circle"></i> Portal Nasabah
        </h4>
        <p class="text-muted mb-0">
            Selamat datang, {{ auth()->user()->name }}!
            <span class="badge badge-nasabah">Nasabah</span>
            @if(isset($customer) && $customer)
                <code class="ms-1">{{ $customer->code }}</code>
            @endif
        </p>
    </div>
    @if(isset($customer) && $customer)
        <div class="d-flex gap-2">
            <a href="{{ route('nasabah.riwayat.index') }}" class="btn btn-outline-success">
                <i class="bi bi-clock-history"></i> Riwayat Lengkap
            </a>
            <a href="{{ route('nasabah.profil.edit') }}" class="btn btn-outline-secondary">
                <i class="bi bi-person-gear"></i> Ubah Profil
            </a>
        </div>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-success shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Saldo Tabungan Saat Ini</div>
                <h3 class="text-success fw-bold mt-1 mb-0">
                    Rp {{ number_format($customer?->balance ?? 0, 2, ',', '.') }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Setoran</div>
                <h5 class="fw-semibold text-success mt-1 mb-0">
                    Rp {{ number_format($totalDeposits ?? 0, 2, ',', '.') }}
                </h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Penarikan</div>
                <h5 class="fw-semibold text-danger mt-1 mb-0">
                    Rp {{ number_format($totalWithdrawals ?? 0, 2, ',', '.') }}
                </h5>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-truck"></i> Status Penjemputan Aktif Terakhir</span>
        @if(isset($customer) && $customer)
            <a href="{{ route('nasabah.penjemputan.create') }}" class="btn btn-sm btn-success">
                <i class="bi bi-plus-circle"></i> Ajukan Penjemputan
            </a>
        @endif
    </div>
    <div class="card-body">
        @if(!empty($activePickup))
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <code>{{ $activePickup->code }}</code>
                    <span class="badge {{ $activePickup->statusBadgeClass() }} ms-1">{{ $activePickup->statusLabel() }}</span>
                    <div class="small text-muted mt-1">
                        Diajukan: {{ $activePickup->requested_date?->format('d-m-Y') }}
                        @if($activePickup->scheduled_date)
                            | Jadwal Jemput: <strong>{{ $activePickup->scheduled_date->format('d-m-Y') }}</strong>
                        @endif
                        @if($activePickup->officer)
                            | Petugas: {{ $activePickup->officer->name }}
                        @endif
                    </div>
                </div>
                <a href="{{ route('nasabah.penjemputan.show', $activePickup) }}" class="btn btn-sm btn-outline-primary">
                    Lihat Detail
                </a>
            </div>
        @else
            <p class="text-muted mb-0">Tidak ada pengajuan penjemputan yang sedang menunggu atau terjadwal.</p>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history"></i> 5 Transaksi Terakhir</span>
        @if(isset($customer) && $customer)
            <a href="{{ route('nasabah.riwayat.index') }}" class="btn btn-sm btn-link">Lihat Semua</a>
        @endif
    </div>
    <div class="card-body">
        @if(empty($recentTransactions) || $recentTransactions->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2"></i>
                <p class="mt-2 mb-0">Belum ada transaksi tercatat.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Waktu</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-end">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $tx)
                            <tr>
                                <td><code>{{ $tx->code }}</code></td>
                                <td>{{ $tx->transacted_at?->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if($tx->type === 'setoran')
                                        <span class="badge bg-success">Setoran</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Penarikan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tx->type === 'setoran')
                                        {{ $tx->waste_type_name }} ({{ number_format($tx->weight, 3, ',', '.') }} {{ $tx->waste_unit }})
                                    @else
                                        Penarikan saldo tabungan
                                    @endif
                                </td>
                                <td class="text-end fw-semibold {{ $tx->type === 'setoran' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->type === 'setoran' ? '+' : '-' }} Rp {{ number_format($tx->amount, 2, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    @if($tx->type === 'setoran')
                                        <a href="{{ route('nasabah.riwayat.deposit', $tx->record_id) }}" class="btn btn-sm btn-outline-primary">Bukti</a>
                                    @else
                                        <a href="{{ route('nasabah.riwayat.withdrawal', $tx->record_id) }}" class="btn btn-sm btn-outline-primary">Bukti</a>
                                    @endif
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
