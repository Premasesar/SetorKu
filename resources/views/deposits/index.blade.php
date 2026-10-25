@extends('layouts.app')

@section('title', 'Riwayat Setoran')

@section('content')
@php($isPetugas = auth()->user()->isPetugas())
@php($routePrefix = $isPetugas ? 'petugas.setoran' : 'admin.setoran')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-arrow-down-circle"></i> Riwayat Setoran</h4>
    @if($isPetugas)
        <a href="{{ route('petugas.setoran.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Catat Setoran Baru
        </a>
    @endif
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route($routePrefix . '.index') }}" class="row g-2 mb-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari nama/kode nasabah, kode setoran, atau jenis sampah...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($search !== '')
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($deposits->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2 mb-0">Belum ada riwayat setoran.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                                <td><code>{{ $deposit->code }}</code></td>
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
