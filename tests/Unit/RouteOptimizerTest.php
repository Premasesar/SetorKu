<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RouteOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * Unit Test murni (tanpa database) untuk RouteOptimizer — PBI-11.
 */
class RouteOptimizerTest extends TestCase
{
    private RouteOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizer = new RouteOptimizer();
    }

    public function test_haversine_zero_zero_to_zero_one_is_approximately_111_19_km(): void
    {
        $distance = $this->optimizer->haversine(0.0, 0.0, 0.0, 1.0);

        // 2 * pi * 6371.0088 / 360 = 111.1950802 km
        $this->assertEqualsWithDelta(111.195, $distance, 0.01);
        $this->assertSame(0.0, $this->optimizer->haversine(-7.797, 110.370, -7.797, 110.370));

        // Simetris
        $ab = $this->optimizer->haversine(-7.792576, 110.365843, -7.834210, 110.354890);
        $ba = $this->optimizer->haversine(-7.834210, 110.354890, -7.792576, 110.365843);
        $this->assertEqualsWithDelta($ab, $ba, 1e-12);
    }

    public function test_collinear_points_produce_predictable_closed_route_and_distance(): void
    {
        // Titik segaris pada lintang -7.800000:
        // 0 (Depot): 110.35, 1: 110.39 (paling timur), 2: 110.36, 3: 110.38
        $points = [
            ['lat' => -7.800000, 'lng' => 110.350000], // 0: Depot
            ['lat' => -7.800000, 'lng' => 110.390000], // 1: terjauh
            ['lat' => -7.800000, 'lng' => 110.360000], // 2: terdekat
            ['lat' => -7.800000, 'lng' => 110.380000], // 3: tengah
        ];

        $matrix = $this->optimizer->buildDistanceMatrix($points);
        $nnTour = $this->optimizer->nearestNeighbor($matrix);
        $twoOptTour = $this->optimizer->twoOpt($nnTour, $matrix);

        // Dari 110.35 harus berurutan ke timur: 2 (110.36) -> 3 (110.38) -> 1 (110.39) -> kembali 0 (110.35)
        $this->assertSame([0, 2, 3, 1, 0], $nnTour);
        $this->assertSame([0, 2, 3, 1, 0], $twoOptTour);

        // Total jarak tertutup titik segaris = 2 x jarak ujung barat (0) ke ujung timur (1)
        $expectedRoundTrip = 2.0 * $this->optimizer->haversine(-7.800000, 110.350000, -7.800000, 110.390000);
        $actualDistance = $this->optimizer->routeDistance($twoOptTour, $matrix);

        $this->assertEqualsWithDelta($expectedRoundTrip, $actualDistance, 1e-6);
    }

    public function test_four_points_on_square_corners_with_crossed_initial_tour_is_uncrossed_by_two_opt(): void
    {
        // Empat titik di sudut persegi sekitar Yogyakarta:
        // 0 (SW - Depot), 1 (NE - diagonal!), 2 (SE), 3 (NW)
        $points = [
            ['lat' => -7.800000, 'lng' => 110.350000], // 0: Barat Daya (Depot)
            ['lat' => -7.750000, 'lng' => 110.400000], // 1: Timur Laut
            ['lat' => -7.800000, 'lng' => 110.400000], // 2: Tenggara
            ['lat' => -7.750000, 'lng' => 110.350000], // 3: Barat Laut
        ];

        $matrix = $this->optimizer->buildDistanceMatrix($points);

        // Rute awal menyilang melewati dua diagonal persegi: 0(SW) -> 1(NE) -> 2(SE) -> 3(NW) -> 0(SW)
        $crossedTour = [0, 1, 2, 3, 0];
        $crossedDistance = $this->optimizer->routeDistance($crossedTour, $matrix);

        $uncrossedTour = $this->optimizer->twoOpt($crossedTour, $matrix);
        $uncrossedDistance = $this->optimizer->routeDistance($uncrossedTour, $matrix);

        // Keliling persegi tanpa persilangan hanya mungkin searah atau berlawanan jarum jam
        $this->assertTrue(
            $uncrossedTour === [0, 2, 1, 3, 0] || $uncrossedTour === [0, 3, 1, 2, 0],
            'Rute hasil 2-Opt harus menghilangkan persilangan diagonal persegi.'
        );

        $expectedPerimeter = $matrix[0][2] + $matrix[2][1] + $matrix[1][3] + $matrix[3][0];
        $this->assertLessThan($crossedDistance, $uncrossedDistance);
        $this->assertEqualsWithDelta($expectedPerimeter, $uncrossedDistance, 1e-6);
    }

    public function test_two_opt_is_never_longer_than_nearest_neighbor_and_visits_all_points_once(): void
    {
        $datasets = [
            // Dataset 1: Koordinat nyata Yogyakarta (Petugas Dua seeder)
            [
                ['lat' => -7.7970680, 'lng' => 110.3705290], // 0 Depot
                ['lat' => -7.8190240, 'lng' => 110.3705180], // 1 Prawirotaman
                ['lat' => -7.8342100, 'lng' => 110.3548900], // 2 Sewon
                ['lat' => -7.8021450, 'lng' => 110.3912800], // 3 Kusumanegara
                ['lat' => -7.8076400, 'lng' => 110.3778100], // 4 Taman Siswa
            ],
            // Dataset 2: 6 titik menyebar di DIY
            [
                ['lat' => -7.7970680, 'lng' => 110.3705290], // 0 Depot
                ['lat' => -7.7630510, 'lng' => 110.3789120], // 1 Kaliurang
                ['lat' => -7.8278900, 'lng' => 110.3997200], // 2 Kotagede
                ['lat' => -7.7819300, 'lng' => 110.3412500], // 3 Godean
                ['lat' => -7.7745190, 'lng' => 110.3894120], // 4 Gejayan
                ['lat' => -7.8214700, 'lng' => 110.3681500], // 5 Parangtritis
                ['lat' => -7.7618900, 'lng' => 110.3614200], // 6 Magelang
            ],
        ];

        foreach ($datasets as $points) {
            $n = count($points) - 1;
            $matrix = $this->optimizer->buildDistanceMatrix($points);

            $nnTour = $this->optimizer->nearestNeighbor($matrix);
            $nnDistance = $this->optimizer->routeDistance($nnTour, $matrix);

            $twoOptTour = $this->optimizer->twoOpt($nnTour, $matrix);
            $twoOptDistance = $this->optimizer->routeDistance($twoOptTour, $matrix);

            // 1. Berangkat dari titik 0 dan kembali ke titik 0
            $this->assertSame(0, $twoOptTour[0]);
            $this->assertSame(0, $twoOptTour[count($twoOptTour) - 1]);
            $this->assertCount($n + 2, $twoOptTour);

            // 2. Semua titik 1..N dikunjungi tepat sekali
            $visitedStops = array_slice($twoOptTour, 1, $n);
            sort($visitedStops);
            $this->assertSame(range(1, $n), $visitedStops);

            // 3. Hasil twoOpt tidak pernah lebih panjang dari nearestNeighbor
            $this->assertLessThanOrEqual($nnDistance + 1e-9, $twoOptDistance);
        }
    }

    public function test_coordinate_validation_rules(): void
    {
        $this->assertNull($this->optimizer->validateCoordinate(-7.7970680, 110.3705290));
        $this->assertNotNull($this->optimizer->validateCoordinate(null, 110.37));
        $this->assertNotNull($this->optimizer->validateCoordinate('', '110.37'));
        $this->assertNotNull($this->optimizer->validateCoordinate('abc', '110.37'));
        $this->assertNotNull($this->optimizer->validateCoordinate(0, 0));
        $this->assertNotNull($this->optimizer->validateCoordinate('0.0000000', '0.0000000'));
        $this->assertNotNull($this->optimizer->validateCoordinate(-12.5, 110.37));
        $this->assertNotNull($this->optimizer->validateCoordinate(7.1, 110.37));
        $this->assertNotNull($this->optimizer->validateCoordinate(-7.8, 94.9));
        $this->assertNotNull($this->optimizer->validateCoordinate(-7.8, 141.1));
    }
}
