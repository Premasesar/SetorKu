@extends('layouts.app')

@section('title', 'Laporan Rekap Bulanan')

@section('content')
@php($months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
])

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-journal-text"></i> Laporan Rekap Bulanan</h4>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.laporan.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="month" class="form-label mb-1">Bulan</label>
                <select id="month" name="month" class="form-select">
                    @foreach($months as $mNum => $mName)
                        <option value="{{ $mNum }}" @selected($month === $mNum)>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="year" class="form-label mb-1">Tahun</label>
                <input type="number" id="year" name="year" min="2020" max="2100" value="{{ $year }}" class="form-control">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success"><i class="bi bi-funnel"></i> Tampilkan</button>
            </div>
        </form>
    </div>
</div>

{{-- (1) Rekap Setoran per Jenis Sampah --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        1. Rekap Setoran per Jenis Sampah ({{ $months[$month] ?? $month }} {{ $year }})
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jenis Sampah</th>
                        <th>Satuan</th>
                        <th class="text-end">Jumlah Transaksi</th>
                        <th class="text-end">Total Berat</th>
                        <th class="text-end">Total Nilai (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['deposits_by_waste_type'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->unit }}</td>
                            <td class="text-end">{{ number_format($row->transactions_count, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($row->total_weight, 3, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row->total_value, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada jenis sampah terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2">Total</td>
                        <td class="text-end">{{ number_format($report['deposit_totals']['transactions_count'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($report['deposit_totals']['total_weight'], 3, ',', '.') }}</td>
                        <td class="text-end text-success">Rp {{ number_format($report['deposit_totals']['total_value'], 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- (2) Rekap Penarikan --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                2. Rekap Penarikan Saldo ({{ $months[$month] ?? $month }} {{ $year }})
            </div>
            <div class="card-body">
                <table class="table table-bordered align-middle mb-0">
                    <tbody>
                        <tr>
                            <th>Jumlah Transaksi Penarikan</th>
                            <td class="text-end fw-semibold">{{ number_format($report['withdrawal_summary']['transactions_count'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Nominal Penarikan</th>
                            <td class="text-end fw-bold text-danger">Rp {{ number_format($report['withdrawal_summary']['total_amount'], 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- (3) Rekap Penjemputan per Status --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                3. Rekap Penjemputan per Status ({{ $months[$month] ?? $month }} {{ $year }})
            </div>
            <div class="card-body">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['pickups_by_status'] as $item)
                            <tr>
                                <td><span class="badge {{ $item->badge_class }}">{{ $item->label }}</span></td>
                                <td class="text-end">{{ number_format($item->count, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>Total Pengajuan</td>
                            <td class="text-end">{{ number_format($report['pickup_total'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
