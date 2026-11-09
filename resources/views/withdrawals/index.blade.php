@extends('layouts.ui-staff')

@section('title', 'Riwayat Penarikan')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.penarikan' : 'admin.penarikan')

<x-page-hero
    title="Riwayat Penarikan Saldo"
    subtitle="Rekapitulasi transaksi pencairan saldo tabungan sampah nasabah yang telah tercatat."
    icon="bi-arrow-up-circle"
>
    @if($isPetugas)
        <x-slot:actions>
            <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Catat Penarikan Baru
            </a>
        </x-slot:actions>
    @endif
</x-page-hero>

<div class="card sk-card shadow-sm">
    <div class="card-body p-3 p-md-4">
        <x-table-toolbar
            :action="route($routePrefix . '.index')"
            search-name="q"
            :search-value="$search"
            placeholder="Cari nama/kode nasabah atau kode penarikan..."
            submit-label="Cari"
            :reset-url="$search !== '' ? route($routePrefix . '.index') : null"
            reset-label="Reset"
            class="mb-3"
        />

        @if($withdrawals->isEmpty())
            <div class="sk-empty-state text-center text-muted py-5">
                <div class="sk-empty-icon mx-auto mb-2">
                    <i class="bi bi-inbox fs-3"></i>
                </div>
                <p class="mt-2 mb-0">Belum ada riwayat penarikan saldo.</p>
            </div>
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table sk-table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Waktu</th>
                            <th>Nasabah</th>
                            <th class="text-end">Nominal Penarikan</th>
                            <th>Petugas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($withdrawals as $withdrawal)
                            <tr>
                                <td><code>{{ $withdrawal->code }}</code></td>
                                <td>{{ $withdrawal->created_at?->format('d-m-Y H:i') }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $withdrawal->customer?->name }}</span>
                                    <br><small class="text-muted">{{ $withdrawal->customer?->code }}</small>
                                </td>
                                <td class="text-end fw-semibold text-danger">Rp {{ number_format($withdrawal->amount, 2, ',', '.') }}</td>
                                <td>{{ $withdrawal->officer?->name }}</td>
                                <td class="text-end">
                                    <a href="{{ route($routePrefix . '.show', $withdrawal) }}" class="btn btn-sm btn-outline-primary">
                                        Bukti
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (<768px) --}}
            <div class="d-md-none d-flex flex-column gap-3">
                @foreach($withdrawals as $withdrawal)
                    <div class="card border shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <code>{{ $withdrawal->code }}</code>
                                    <div class="fw-bold mt-1">{{ $withdrawal->customer?->name }}</div>
                                    <small class="text-muted">{{ $withdrawal->customer?->code }}</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">{{ $withdrawal->created_at?->format('d-m-Y H:i') }}</small>
                                    <span class="fw-bold text-danger">Rp {{ number_format($withdrawal->amount, 2, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="small text-muted mb-2">
                                <div><strong>Petugas:</strong> {{ $withdrawal->officer?->name }}</div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <a href="{{ route($routePrefix . '.show', $withdrawal) }}" class="btn btn-sm btn-outline-primary">
                                    Bukti
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-2 border-top">
                <small class="text-muted">
                    Menampilkan {{ $withdrawals->firstItem() }}–{{ $withdrawals->lastItem() }} dari {{ $withdrawals->total() }} penarikan
                </small>
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
