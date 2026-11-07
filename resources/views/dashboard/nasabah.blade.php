@extends('layouts.ui-nasabah')

@section('title', 'Portal Nasabah')

@section('hero')
    <x-page-hero
        title="Portal Nasabah"
        icon="bi-person-circle"
        :breadcrumbs="[['label' => 'Portal Nasabah'], ['label' => 'Dashboard']]"
    >
        Selamat datang, {{ auth()->user()->name }}!
        <span class="badge badge-nasabah ms-1">Nasabah</span>
        @if(isset($customer) && $customer)
            <code class="ms-1">{{ $customer->code }}</code>
        @endif

        @if(isset($customer) && $customer)
            <x-slot:actions>
                <a href="{{ route('nasabah.kartu.show') }}" class="btn btn-success">
                    <i class="bi bi-qr-code"></i> Kartu Saya
                </a>
                <a href="{{ route('nasabah.riwayat.index') }}" class="btn btn-outline-success">
                    <i class="bi bi-clock-history"></i> Riwayat Lengkap
                </a>
                <a href="{{ route('nasabah.profil.edit') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-person-gear"></i> Ubah Profil
                </a>
            </x-slot:actions>
        @endif
    </x-page-hero>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-stat-card
            label="Saldo Tabungan Saat Ini"
            :value="'Rp ' . number_format($customer?->balance ?? 0, 2, ',', '.')"
            icon="bi-wallet2"
            variant="success"
            :accent="true"
        />
    </div>
    <div class="col-md-4">
        <x-stat-card
            label="Total Setoran"
            :value="'Rp ' . number_format($totalDeposits ?? 0, 2, ',', '.')"
            icon="bi-arrow-down-circle"
            variant="success"
            :compact="true"
        />
    </div>
    <div class="col-md-4">
        <x-stat-card
            label="Total Penarikan"
            :value="'Rp ' . number_format($totalWithdrawals ?? 0, 2, ',', '.')"
            icon="bi-arrow-up-circle"
            variant="danger"
            :compact="true"
        />
    </div>
</div>

<div class="card border-success shadow-sm mb-4">
    <div class="card-header bg-success-subtle text-success fw-semibold">
        <i class="bi bi-recycle"></i> Kontribusi saya
    </div>
    <div class="card-body">
        <div class="row g-3 text-center text-md-start">
            <div class="col-md-6">
                <div class="text-muted small">Total Berat Sampah Disetor</div>
                <h4 class="fw-bold text-success mb-0">{{ number_format($totalWeightKg ?? 0, 3, ',', '.') }} kg</h4>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Jumlah Setoran</div>
                <h4 class="fw-bold mb-0">{{ $depositsCount ?? 0 }} setoran</h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-truck text-success me-1"></i> Status Penjemputan Aktif Terakhir</span>
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
                    <x-status-pill :status="$activePickup->status" :label="$activePickup->statusLabel()" class="ms-1" />
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
        <span class="fw-semibold"><i class="bi bi-clock-history text-success me-1"></i> 5 Transaksi Terakhir</span>
        @if(isset($customer) && $customer)
            <a href="{{ route('nasabah.riwayat.index') }}" class="btn btn-sm btn-link">Lihat Semua</a>
        @endif
    </div>
    <div class="card-body">
        @if(empty($recentTransactions) || $recentTransactions->isEmpty())
            <x-empty-state
                icon="bi-inbox"
                message="Belum ada transaksi tercatat."
            />
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
                                        <x-status-pill status="setoran" label="Setoran" />
                                    @else
                                        <x-status-pill status="penarikan" label="Penarikan" />
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
