@extends('layouts.app')

@section('title', 'Dampak Pengelolaan Sampah')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line"></i> Dampak Pengelolaan Sampah</h4>
        <small class="text-muted">Ringkasan seluruh waktu berdasarkan data setoran nyata yang tersimpan di sistem.</small>
    </div>
    <a href="{{ route('admin.laporan.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-journal-text"></i> Laporan Bulanan
    </a>
</div>

{{-- Kartu Metrik Utama Seluruh Waktu --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-success shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Sampah Terkelola (Seluruh Waktu)</div>
                <h3 class="fw-bold text-success mt-1 mb-0">
                    {{ number_format($impact['total_weight_kg'], 3, ',', '.') }} kg
                </h3>
                <div class="small text-muted mt-1">{{ $impact['total_deposits_count'] }} transaksi setoran</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-primary shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Nilai Setoran (Seluruh Waktu)</div>
                <h3 class="fw-bold text-primary mt-1 mb-0">
                    Rp {{ number_format($impact['total_deposit_value'], 2, ',', '.') }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Jumlah Nasabah Aktif</div>
                <h3 class="fw-bold mt-1 mb-0">{{ $impact['active_customers_count'] }}</h3>
                <div class="small text-muted mt-1">Nasabah berstatus aktif</div>
            </div>
        </div>
    </div>
</div>

{{-- Grafik Batang 12 Bulan Terakhir --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-bar-chart"></i> Grafik Berat Sampah Terkelola per Bulan (12 Bulan Terakhir)
    </div>
    <div class="card-body">
        <div style="height: 300px;">
            <canvas id="monthlyWeightBarChart"></canvas>
        </div>
    </div>
</div>

{{-- Tabel Per Jenis Sampah --}}
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-table"></i> Komposisi Sampah Terkelola per Jenis Sampah
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Jenis Sampah</th>
                        <th class="text-end">Jumlah Setoran</th>
                        <th class="text-end">Total Berat (kg)</th>
                        <th class="text-end pe-3">Persentase (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($impact['by_waste_type'] as $row)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $row->name }}</td>
                            <td class="text-end">{{ $row->deposits_count }}</td>
                            <td class="text-end">{{ number_format($row->total_weight_kg, 3, ',', '.') }}</td>
                            <td class="text-end pe-3">{{ number_format($row->percentage, 2, ',', '.') }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada jenis sampah terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">Total</td>
                        <td class="text-end">{{ $impact['total_deposits_count'] }}</td>
                        <td class="text-end">{{ number_format($impact['total_weight_kg'], 3, ',', '.') }}</td>
                        <td class="text-end pe-3">{{ $impact['total_weight_kg'] > 0 ? '100,00%' : '0,00%' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('monthlyWeightBarChart');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const series = @json($impact['monthly_series']);
    const labels = series.map(function (item) { return item.label; });
    const data = series.map(function (item) { return item.weight_kg; });

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Berat Sampah Terkelola (kg)',
                data: data,
                backgroundColor: 'rgba(25, 135, 84, 0.75)',
                borderColor: '#198754',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Kilogram (kg)'
                    }
                }
            }
        }
    });
});
</script>
@endpush
