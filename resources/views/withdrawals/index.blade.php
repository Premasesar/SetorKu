@extends('layouts.app')

@section('title', 'Riwayat Penarikan')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.penarikan' : 'admin.penarikan')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-arrow-up-circle"></i> Riwayat Penarikan Saldo</h4>
    @if($isPetugas)
        <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Catat Penarikan Baru
        </a>
    @endif
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route($routePrefix . '.index') }}" class="row g-2 mb-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari nama/kode nasabah atau kode penarikan...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($search !== '')
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($withdrawals->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2 mb-0">Belum ada riwayat penarikan saldo.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                                    {{ $withdrawal->customer?->name }}
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

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $withdrawals->firstItem() }}–{{ $withdrawals->lastItem() }} dari {{ $withdrawals->total() }} penarikan
                </small>
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
