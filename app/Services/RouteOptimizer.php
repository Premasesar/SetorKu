<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Class murni PHP untuk optimasi rute tertutup penjemputan sampah — PBI-11.
 *
 * Bebas ketergantungan pada database/Eloquent sehingga dapat diuji secara unit murni:
 * - Jarak garis lurus Haversine (R = 6371.0088 km)
 * - Matriks jarak simetris
 * - Konstruksi rute awal Nearest Neighbor dari titik 0 (Bank Sampah) kembali ke titik 0
 * - Perbaikan lokal 2-Opt dengan batas iterasi pengaman
 * - Validasi kualitas koordinat wilayah Indonesia (lat -11..6, lng 95..141, bukan 0,0)
 */
class RouteOptimizer
{
    public const EARTH_RADIUS_KM = 6371.0088;

    public const MIN_LAT_INDONESIA = -11.0;
    public const MAX_LAT_INDONESIA = 6.0;
    public const MIN_LNG_INDONESIA = 95.0;
    public const MAX_LNG_INDONESIA = 141.0;

    private const EPSILON = 1e-9;

    /**
     * Hitung jarak permukaan bola bumi (Haversine) antara dua koordinat dalam kilometer.
     */
    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);

        $a = (sin($dLat / 2.0) ** 2)
            + cos($radLat1) * cos($radLat2) * (sin($dLng / 2.0) ** 2);

        $c = 2.0 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a)));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Bangun matriks jarak N x N dalam km dari daftar titik berindeks 0..N-1.
     *
     * @param  list<array{lat?: float|int|string, lng?: float|int|string, latitude?: float|int|string, longitude?: float|int|string}>  $points
     * @return list<list<float>>
     */
    public function buildDistanceMatrix(array $points): array
    {
        $coords = array_values(array_map([$this, 'extractCoordinates'], $points));
        $count = count($coords);
        $matrix = array_fill(0, $count, array_fill(0, $count, 0.0));

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $distance = $this->haversine(
                    $coords[$i][0],
                    $coords[$i][1],
                    $coords[$j][0],
                    $coords[$j][1],
                );
                $matrix[$i][$j] = $distance;
                $matrix[$j][$i] = $distance;
            }
        }

        return $matrix;
    }

    /**
     * Hitung total jarak sebuah rute berurutan berdasarkan matriks jarak.
     *
     * @param  list<int>  $tour  Misal rute tertutup [0, 2, 1, 3, 0]
     * @param  list<list<float>>  $distanceMatrix
     */
    public function routeDistance(array $tour, array $distanceMatrix): float
    {
        $length = count($tour);
        if ($length < 2) {
            return 0.0;
        }

        $total = 0.0;
        for ($i = 0; $i < $length - 1; $i++) {
            $from = $tour[$i];
            $to = $tour[$i + 1];
            $total += $distanceMatrix[$from][$to];
        }

        return $total;
    }

    /**
     * Algoritma Nearest Neighbor pada rute tertutup:
     * Berangkat dari titik 0 (Bank Sampah), mengunjungi semua titik 1..N tepat sekali,
     * lalu kembali ke titik 0.
     *
     * @param  list<list<float>>  $distanceMatrix
     * @return list<int>  Rute tertutup [0, ..., 0]
     */
    public function nearestNeighbor(array $distanceMatrix): array
    {
        $pointCount = count($distanceMatrix);
        if ($pointCount === 0) {
            throw new InvalidArgumentException('Matriks jarak tidak boleh kosong.');
        }

        if ($pointCount === 1) {
            return [0, 0];
        }

        $visited = array_fill(0, $pointCount, false);
        $visited[0] = true;

        $tour = [0];
        $current = 0;

        for ($step = 1; $step < $pointCount; $step++) {
            $bestNext = -1;
            $bestDistance = INF;

            for ($candidate = 1; $candidate < $pointCount; $candidate++) {
                if ($visited[$candidate]) {
                    continue;
                }

                $d = $distanceMatrix[$current][$candidate];
                if ($d < $bestDistance) {
                    $bestDistance = $d;
                    $bestNext = $candidate;
                }
            }

            $visited[$bestNext] = true;
            $tour[] = $bestNext;
            $current = $bestNext;
        }

        $tour[] = 0;

        return $tour;
    }

    /**
     * Perbaikan lokal 2-Opt pada rute tertutup [0, v1, ..., vn, 0].
     * Titik awal (indeks 0 = depot 0) dan titik akhir (depot 0) dipertahankan tetap.
     * Mengulang pertukaran dua sisi menyilang hingga tidak ada pengurangan jarak
     * atau mencapai batas iterasi pengaman ($maxIterations).
     *
     * @param  list<int>  $tour
     * @param  list<list<float>>  $distanceMatrix
     * @return list<int>
     */
    public function twoOpt(array $tour, array $distanceMatrix, int $maxIterations = 1000): array
    {
        if ($maxIterations < 1) {
            throw new InvalidArgumentException('Batas iterasi harus minimal 1.');
        }

        $tour = array_values($tour);
        if ($tour === []) {
            return [0, 0];
        }

        if ($tour[0] !== 0) {
            throw new InvalidArgumentException('Rute tertutup harus dimulai dari titik 0 (Bank Sampah).');
        }

        if ($tour[count($tour) - 1] !== 0) {
            $tour[] = 0;
        }

        // Jumlah titik kunjungan selain depot awal & akhir: n = count($tour) - 2
        $n = count($tour) - 2;
        if ($n < 2) {
            return $tour;
        }

        $iteration = 0;
        $improved = true;

        while ($improved && $iteration < $maxIterations) {
            $improved = false;
            $iteration++;

            for ($i = 1; $i <= $n - 1; $i++) {
                for ($k = $i + 1; $k <= $n; $k++) {
                    $a = $tour[$i - 1];
                    $b = $tour[$i];
                    $c = $tour[$k];
                    $d = $tour[$k + 1];

                    $currentEdges = $distanceMatrix[$a][$b] + $distanceMatrix[$c][$d];
                    $swappedEdges = $distanceMatrix[$a][$c] + $distanceMatrix[$b][$d];

                    if ($swappedEdges + self::EPSILON < $currentEdges) {
                        $segment = array_reverse(array_slice($tour, $i, $k - $i + 1));
                        array_splice($tour, $i, $k - $i + 1, $segment);
                        $improved = true;
                    }
                }
            }
        }

        return $tour;
    }

    /**
     * Validasi kualitas koordinat Indonesia.
     * Mengembalikan null jika valid, atau pesan alasan dalam Bahasa Indonesia jika tidak valid.
     */
    public function validateCoordinate(mixed $lat, mixed $lng): ?string
    {
        if ($lat === null || $lng === null || trim((string) $lat) === '' || trim((string) $lng) === '') {
            return 'Koordinat kosong.';
        }

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return 'Koordinat bukan angka yang valid.';
        }

        $fLat = (float) $lat;
        $fLng = (float) $lng;

        if (! is_finite($fLat) || ! is_finite($fLng)) {
            return 'Koordinat bukan angka berhingga.';
        }

        if (abs($fLat) < self::EPSILON && abs($fLng) < self::EPSILON) {
            return 'Koordinat tepat 0,0 (tidak valid).';
        }

        if (
            $fLat < self::MIN_LAT_INDONESIA
            || $fLat > self::MAX_LAT_INDONESIA
            || $fLng < self::MIN_LNG_INDONESIA
            || $fLng > self::MAX_LNG_INDONESIA
        ) {
            return 'Koordinat di luar rentang wilayah Indonesia (lat -11..6, lng 95..141).';
        }

        return null;
    }

    /**
     * Pisahkan titik penjemputan menjadi titik valid dan titik yang dikecualikan beserta alasannya.
     *
     * @param  list<array<string, mixed>>  $stops
     * @return array{valid: list<array<string, mixed>>, excluded: list<array{stop: array<string, mixed>, reason: string}>}
     */
    public function partitionStops(array $stops): array
    {
        $valid = [];
        $excluded = [];

        foreach ($stops as $stop) {
            $rawLat = $stop['lat'] ?? $stop['latitude'] ?? null;
            $rawLng = $stop['lng'] ?? $stop['longitude'] ?? null;
            $reason = $this->validateCoordinate($rawLat, $rawLng);

            if ($reason !== null) {
                $excluded[] = [
                    'stop' => $stop,
                    'reason' => $reason,
                ];

                continue;
            }

            $stop['lat'] = (float) $rawLat;
            $stop['lng'] = (float) $rawLng;
            $valid[] = $stop;
        }

        return [
            'valid' => $valid,
            'excluded' => $excluded,
        ];
    }

    /**
     * Jalankan pipeline lengkap optimasi rute tertutup dari Bank Sampah (depot) ke seluruh titik valid.
     * Urutan awal mengikuti urutan elemen $stops yang diberikan (mis. berdasarkan created_at).
     *
     * @param  array{name?: string, lat?: float|int|string, lng?: float|int|string, latitude?: float|int|string, longitude?: float|int|string}  $depot
     * @param  list<array<string, mixed>>  $stops
     * @return array<string, mixed>
     */
    public function optimize(array $depot, array $stops, int $maxIterations = 1000): array
    {
        [$depotLat, $depotLng] = $this->extractCoordinates($depot);
        $depotPoint = [
            'name' => (string) ($depot['name'] ?? 'Bank Sampah'),
            'lat' => $depotLat,
            'lng' => $depotLng,
        ];

        $partitioned = $this->partitionStops($stops);
        $validStops = $partitioned['valid'];
        $excludedStops = $partitioned['excluded'];
        $validCount = count($validStops);

        if ($validCount === 0) {
            return [
                'depot' => $depotPoint,
                'requires_optimization' => false,
                'valid_count' => 0,
                'excluded' => $excludedStops,
                'ordered_stops' => [],
                'return_leg_km' => 0.0,
                'initial_distance_km' => 0.0,
                'nn_distance_km' => 0.0,
                'two_opt_distance_km' => 0.0,
                'savings_km' => 0.0,
                'savings_percent' => 0.0,
                'polyline' => [],
            ];
        }

        $points = array_merge([$depotPoint], $validStops);
        $matrix = $this->buildDistanceMatrix($points);

        // Urutan awal berdasarkan waktu permintaan masuk (0 -> 1 -> 2 -> ... -> N -> 0)
        $initialTour = array_merge([0], range(1, $validCount), [0]);
        $initialDistance = $this->routeDistance($initialTour, $matrix);

        if ($validCount === 1) {
            $legToStop = $matrix[0][1];
            $returnLeg = $matrix[1][0];
            $singleStop = $validStops[0];
            $singleStop['sequence'] = 1;
            $singleStop['leg_distance_km'] = $legToStop;
            $singleStop['cumulative_distance_km'] = $legToStop;

            return [
                'depot' => $depotPoint,
                'requires_optimization' => false,
                'valid_count' => 1,
                'excluded' => $excludedStops,
                'ordered_stops' => [$singleStop],
                'return_leg_km' => $returnLeg,
                'initial_distance_km' => $initialDistance,
                'nn_distance_km' => $initialDistance,
                'two_opt_distance_km' => $initialDistance,
                'savings_km' => 0.0,
                'savings_percent' => 0.0,
                'polyline' => [
                    [$depotLat, $depotLng],
                    [$singleStop['lat'], $singleStop['lng']],
                    [$depotLat, $depotLng],
                ],
            ];
        }

        $nnTour = $this->nearestNeighbor($matrix);
        $nnDistance = $this->routeDistance($nnTour, $matrix);

        $twoOptTour = $this->twoOpt($nnTour, $matrix, $maxIterations);
        $twoOptDistance = $this->routeDistance($twoOptTour, $matrix);

        $orderedStops = [];
        $polyline = [[$depotLat, $depotLng]];
        $cumulative = 0.0;

        for ($pos = 1; $pos <= $validCount; $pos++) {
            $prevIdx = $twoOptTour[$pos - 1];
            $currIdx = $twoOptTour[$pos];
            $leg = $matrix[$prevIdx][$currIdx];
            $cumulative += $leg;

            $stopData = $validStops[$currIdx - 1];
            $stopData['sequence'] = $pos;
            $stopData['leg_distance_km'] = $leg;
            $stopData['cumulative_distance_km'] = $cumulative;

            $orderedStops[] = $stopData;
            $polyline[] = [$stopData['lat'], $stopData['lng']];
        }

        $lastIdx = $twoOptTour[$validCount];
        $returnLeg = $matrix[$lastIdx][0];
        $polyline[] = [$depotLat, $depotLng];

        $savingsKm = $initialDistance - $twoOptDistance;
        $savingsPercent = $initialDistance > 0.0
            ? ($savingsKm / $initialDistance) * 100.0
            : 0.0;

        return [
            'depot' => $depotPoint,
            'requires_optimization' => true,
            'valid_count' => $validCount,
            'excluded' => $excludedStops,
            'ordered_stops' => $orderedStops,
            'return_leg_km' => $returnLeg,
            'initial_distance_km' => $initialDistance,
            'nn_distance_km' => $nnDistance,
            'two_opt_distance_km' => $twoOptDistance,
            'savings_km' => $savingsKm,
            'savings_percent' => $savingsPercent,
            'polyline' => $polyline,
        ];
    }

    /**
     * @param  array<string, mixed>  $point
     * @return array{0: float, 1: float}
     */
    private function extractCoordinates(array $point): array
    {
        $rawLat = $point['lat'] ?? $point['latitude'] ?? null;
        $rawLng = $point['lng'] ?? $point['longitude'] ?? null;

        if ($rawLat === null || $rawLng === null || ! is_numeric($rawLat) || ! is_numeric($rawLng)) {
            throw new InvalidArgumentException('Titik koordinat tidak valid.');
        }

        return [(float) $rawLat, (float) $rawLng];
    }
}
