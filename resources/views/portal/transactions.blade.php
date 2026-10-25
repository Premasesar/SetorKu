@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-clock-history"></i> Riwayat Setoran & Penarikan</h4>
        <small class="text-muted">Nasabah: {{ $customer->name }} (<code>{{ $customer->code }}</code>) — Saldo: <strong>Rp {{ number_format($customer->balance, 2, ',', '.') }}</strong></small>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('nasabah.riwayat.index') }}" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="all" @selected($type === 'all')>Semua Transaksi</option>
                    <option value="setoran" @selected($type === 'setoran')>Setoran Saja</option>
                    <option value="penarikan" @selected($type === 'penarikan')>Penarikan Saja</option>
                </select>
            </div>
            @if($type !== 'all')
                <div class="col-auto">
                    <a href="{{ route('nasabah.riwayat.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            @endif
        </form>

        @if($transactions->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2 mb-0">Belum ada riwayat transaksi.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Waktu</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th>Petugas</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr>
                                <td><code>{{ $tx->code }}</code></td>
                                <td>{{ $tx->transacted_at?->format('d-m-Y H:i:s') }}</td>
                                <td>
                                    @if($tx->type === 'setoran')
                                        <span class="badge bg-success">Setoran</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Penarikan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tx->type === 'setoran')
                                        {{ $tx->waste_type_name }} — {{ number_format($tx->weight, 3, ',', '.') }} {{ $tx->waste_unit }}
                                        @ Rp {{ number_format($tx->price_per_unit, 2, ',', '.') }}
                                    @else
                                        Penarikan saldo tabungan
                                    @endif
                                </td>
                                <td>{{ $tx->officer_name }}</td>
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

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} dari {{ $transactions->total() }} transaksi
                </small>
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
