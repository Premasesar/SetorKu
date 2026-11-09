@once
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <style>
        .officer-route-icon {
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
        .officer-depot-icon {
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
@endonce

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">
            <i class="bi bi-map"></i> Peta Rute Penjemputan Saya ({{ $todayRoute['date'] }}) — Read-Only
        </span>
        @if(!empty($todayRoute['markers']))
            <span class="badge bg-success">
                {{ count($todayRoute['markers']) }} titik | Estimasi garis lurus: {{ number_format($todayRoute['total_distance_km'], 2, ',', '.') }} km
            </span>
        @endif
    </div>
    <div class="card-body">
        @if(empty($todayRoute['markers']))
            <p class="text-muted mb-0">Belum ada titik tugas penjemputan berkoordinat valid pada tanggal {{ $todayRoute['date'] }}.</p>
        @else
            <div id="officer-route-map" class="sk-map sk-map--md rounded border mb-2" style="height: 340px;"></div>
            <small class="text-muted">
                Urutan mengikuti hasil optimasi urutan rute Admin. Titik awal & akhir adalah <strong>{{ $todayRoute['depot']['name'] }}</strong> (jarak garis lurus Haversine).
            </small>
        @endif
    </div>
</div>

@if(!empty($todayRoute['markers']))
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const depot = @json($todayRoute['depot']);
    const markers = @json($todayRoute['markers']);
    const polylineCoords = @json($todayRoute['polyline']);

    const map = L.map('officer-route-map').setView([depot.lat, depot.lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const depotIcon = L.divIcon({
        className: 'officer-depot-icon',
        html: 'BS',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });

    L.marker([depot.lat, depot.lng], { icon: depotIcon })
        .addTo(map)
        .bindPopup('<strong>0. ' + depot.name + '</strong><br>Titik Berangkat & Kembali');

    markers.forEach(function (stop) {
        const icon = L.divIcon({
            className: 'officer-route-icon',
            html: String(stop.display_number),
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        L.marker([stop.lat, stop.lng], { icon: icon })
            .addTo(map)
            .bindPopup(
                '<strong>#' + stop.display_number + ' — ' + stop.code + '</strong><br>' +
                stop.customer_name + '<br><small>' + stop.address + '</small>'
            );
    });

    if (polylineCoords.length >= 2) {
        const line = L.polyline(polylineCoords, { color: '#198754', weight: 4, opacity: 0.85 }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [30, 30] });
    }
});
</script>
@endpush
@endif
