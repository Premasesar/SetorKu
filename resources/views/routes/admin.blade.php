@extends('layouts.app')

@section('title', 'Rute Penjemputan')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .route-number-icon {
        background-color: #198754;
        color: #fff;
        border: 2px solid #fff;
        border-radius: 50%;
        font-weight: 700;
        font-size: 13px;
        line-height: 26px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.35);
    }
    .depot-marker-icon {
        background-color: #0d6efd;
        color: #fff;
        border: 2px solid #fff;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
        line-height: 26px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.35);
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-signpost-split"></i> Optimasi Rute Penjemputan</h4>
        <small class="text-muted">Hitung dan simpan urutan kunjungan penjemputan terjadwal (berangkat dan kembali ke {{ $depot['name'] }}).</small>
    </div>
    <a href="{{ route('admin.penjemputan.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-truck"></i> Daftar Penjemputan
    </a>
</div>

<div class="alert alert-secondary small mb-3">
    <i class="bi bi-info-circle-fill"></i>
    <strong>Catatan Metodologi:</strong> Perhitungan menggunakan <strong>jarak garis lurus (Haversine)</strong>, bukan jarak jalan sebenarnya, dan algoritma heuristik <strong>Nearest Neighbor + 2-Opt tidak menjamin optimum global</strong>.
</div>

{{-- Filter Tanggal & Petugas --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.rute.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="date" class="form-label mb-1">Tanggal Jadwal Jemput</label>
                <input type="date" id="date" name="date" value="{{ $selectedDate }}" class="form-control" required>
            </div>
            <div class="col-md-5">
                <label for="officer_id" class="form-label mb-1">Petugas Aktif</label>
                <select id="officer_id" name="officer_id" class="form-select" required>
                    <option value="">-- Pilih Petugas --</option>
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" @selected((int) $selectedOfficerId === $officer->id)>
                            {{ $officer->name }} ({{ $officer->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-calculator"></i> Hitung rute
                </button>
            </div>
        </form>
    </div>
</div>

@if($calculation !== null)
    {{-- Daftar titik yang dikecualikan --}}
    @if(!empty($calculation['excluded']))
        <div class="card border-warning shadow-sm mb-3">
            <div class="card-header bg-warning-subtle fw-semibold text-dark">
                <i class="bi bi-exclamation-triangle-fill"></i> Titik Dikecualikan dari Perhitungan ({{ count($calculation['excluded']) }})
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kode</th>
                                <th>Nasabah</th>
                                <th>Koordinat Mentah</th>
                                <th class="pe-3">Alasan Dikecualikan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calculation['excluded'] as $item)
                                <tr>
                                    <td class="ps-3"><code>{{ $item['stop']['code'] ?? '-' }}</code></td>
                                    <td>{{ $item['stop']['customer_name'] ?? '-' }}</td>
                                    <td><code>{{ $item['stop']['latitude'] ?? 'null' }}, {{ $item['stop']['longitude'] ?? 'null' }}</code></td>
                                    <td class="pe-3 text-danger">{{ $item['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if(!$calculation['requires_optimization'])
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle"></i>
            <strong>Optimasi tidak diperlukan:</strong> Jumlah titik penjemputan terjadwal dengan koordinat valid pada tanggal <strong>{{ $selectedDate }}</strong> untuk petugas <strong>{{ $selectedOfficer?->name }}</strong> kurang dari 2 (ditemukan {{ $calculation['valid_count'] }} titik valid).
        </div>

        @if($calculation['valid_count'] === 1)
            <form method="POST" action="{{ route('admin.rute.save') }}" class="mb-3">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <input type="hidden" name="officer_id" value="{{ $selectedOfficer?->id }}">
                <button type="submit" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-save"></i> Simpan urutan rute (1 titik)
                </button>
            </form>
        @endif
    @else
        {{-- Ringkasan 3 Total Jarak & Persentase Penghematan --}}
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Urutan Awal (Waktu Masuk)</div>
                        <h4 class="fw-bold mt-1 mb-0">{{ number_format($calculation['initial_distance_km'], 2, ',', '.') }} km</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-primary">
                    <div class="card-body">
                        <div class="text-muted small">Hasil Nearest Neighbor</div>
                        <h4 class="fw-bold text-primary mt-1 mb-0">{{ number_format($calculation['nn_distance_km'], 2, ',', '.') }} km</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-success">
                    <div class="card-body">
                        <div class="text-muted small">Hasil Nearest Neighbor + 2-Opt</div>
                        <h4 class="fw-bold text-success mt-1 mb-0">{{ number_format($calculation['two_opt_distance_km'], 2, ',', '.') }} km</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 bg-success-subtle border-success">
                    <div class="card-body">
                        <div class="text-muted small">Penghematan vs Urutan Awal</div>
                        <h4 class="fw-bold text-success mt-1 mb-0">{{ number_format($calculation['savings_percent'], 2, ',', '.') }}%</h4>
                        <small class="text-muted">Hemat {{ number_format($calculation['savings_km'], 2, ',', '.') }} km</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <span class="text-muted small">
                Petugas: <strong>{{ $selectedOfficer?->name }}</strong> | Tanggal: <strong>{{ $selectedDate }}</strong> | Titik Valid: <strong>{{ $calculation['valid_count'] }}</strong>
            </span>
            <form method="POST" action="{{ route('admin.rute.save') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <input type="hidden" name="officer_id" value="{{ $selectedOfficer?->id }}">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan urutan rute
                </button>
            </form>
        </div>

        {{-- Peta Leaflet Rute Tertutup --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-map"></i> Peta Rute Penjemputan Tertutup ({{ $depot['name'] }})
            </div>
            <div class="card-body">
                <div id="admin-route-map" class="rounded border" style="height: 400px;"></div>
            </div>
        </div>

        {{-- Tabel Urutan Kunjungan --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-list-ol"></i> Tabel Urutan Rute Penjemputan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">No.</th>
                                <th>Kode JMP</th>
                                <th>Nasabah</th>
                                <th>Alamat</th>
                                <th class="text-end">Jarak dari Titik Sebelumnya (km)</th>
                                <th class="text-end pe-3">Jarak Kumulatif (km)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-primary">
                                <td class="ps-3"><span class="badge bg-primary">0</span></td>
                                <td><code>DEPOT</code></td>
                                <td class="fw-semibold">{{ $depot['name'] }}</td>
                                <td>Titik Keberangkatan ({{ number_format($depot['lat'], 6) }}, {{ number_format($depot['lng'], 6) }})</td>
                                <td class="text-end">0,00</td>
                                <td class="text-end pe-3">0,00</td>
                            </tr>
                            @foreach($calculation['ordered_stops'] as $stop)
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-success">{{ $stop['sequence'] }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.penjemputan.show', $stop['id']) }}">
                                            <code>{{ $stop['code'] }}</code>
                                        </a>
                                    </td>
                                    <td>
                                        {{ $stop['customer_name'] }}
                                        <br><small class="text-muted">{{ $stop['customer_code'] }}</small>
                                    </td>
                                    <td>{{ $stop['address'] }}</td>
                                    <td class="text-end">{{ number_format($stop['leg_distance_km'], 2, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-semibold">{{ number_format($stop['cumulative_distance_km'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light fw-semibold">
                                <td class="ps-3"><span class="badge bg-primary">0</span></td>
                                <td><code>DEPOT</code></td>
                                <td>{{ $depot['name'] }}</td>
                                <td>Kembali ke Bank Sampah (Rute Tertutup)</td>
                                <td class="text-end">{{ number_format($calculation['return_leg_km'], 2, ',', '.') }}</td>
                                <td class="text-end pe-3 text-success">{{ number_format($calculation['two_opt_distance_km'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endif
@endsection

@push('scripts')
@if($calculation !== null && $calculation['requires_optimization'])
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const depot = @json($depot);
    const stops = @json($calculation['ordered_stops']);
    const polylineCoords = @json($calculation['polyline']);

    const map = L.map('admin-route-map').setView([depot.lat, depot.lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const depotIcon = L.divIcon({
        className: 'depot-marker-icon',
        html: 'BS',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });

    L.marker([depot.lat, depot.lng], { icon: depotIcon })
        .addTo(map)
        .bindPopup('<strong>0. ' + depot.name + '</strong><br>Titik Berangkat & Kembali');

    stops.forEach(function (stop) {
        const icon = L.divIcon({
            className: 'route-number-icon',
            html: String(stop.sequence),
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        L.marker([stop.lat, stop.lng], { icon: icon })
            .addTo(map)
            .bindPopup(
                '<strong>#' + stop.sequence + ' — ' + stop.code + '</strong><br>' +
                stop.customer_name + '<br><small>' + stop.address + '</small>'
            );
    });

    if (polylineCoords.length >= 2) {
        const line = L.polyline(polylineCoords, { color: '#198754', weight: 4, opacity: 0.85 }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [30, 30] });
    }
});
</script>
@endif
@endpush
