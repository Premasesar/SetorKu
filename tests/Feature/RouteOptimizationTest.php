<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature Test PBI-11: Optimasi Rute Penjemputan (Admin menghitung & menyimpan, Petugas melihat).
 */
class RouteOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer1;
    private User $officer2;
    private User $nasabahUser;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'setorku.bank_sampah.name' => 'Bank Sampah SetorKu Yogyakarta',
            'setorku.bank_sampah.lat' => -7.7970680,
            'setorku.bank_sampah.lng' => 110.3705290,
        ]);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->officer1 = User::create([
            'name' => 'Petugas Satu', 'email' => 'p1@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->officer2 = User::create([
            'name' => 'Petugas Dua', 'email' => 'p2@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->nasabahUser = User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customer = $this->nasabahUser->customer()->create([
            'name' => 'Budi Santoso',
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
            'latitude' => -7.7925760,
            'longitude' => 110.3658430,
        ]);
    }

    private function createScheduledPickup(
        User $officer,
        string $date,
        float $lat,
        float $lng,
        string $address = 'Alamat Uji Yogyakarta'
    ): PickupRequest {
        $p = new PickupRequest([
            'requested_date' => $date,
            'address' => $address,
            'latitude' => $lat,
            'longitude' => $lng,
            'notes' => 'Catatan uji rute',
        ]);
        $p->customer_id = $this->customer->id;
        $p->status = PickupRequest::STATUS_TERJADWAL;
        $p->scheduled_date = $date;
        $p->officer_id = $officer->id;
        $p->scheduled_by = $this->admin->id;
        $p->scheduled_at = now();
        $p->save();

        return $p;
    }

    public function test_role_access_control_on_route_pages(): void
    {
        $today = now()->toDateString();

        $this->actingAs($this->admin)
            ->get(route('admin.rute.index'))
            ->assertOk();

        $this->actingAs($this->officer1)
            ->get(route('admin.rute.index'))
            ->assertForbidden();

        $this->actingAs($this->officer1)
            ->post(route('admin.rute.save'), ['date' => $today, 'officer_id' => $this->officer1->id])
            ->assertForbidden();

        $this->actingAs($this->nasabahUser)
            ->get(route('admin.rute.index'))
            ->assertForbidden();

        $this->actingAs($this->nasabahUser)
            ->get(route('petugas.rute.show'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('petugas.rute.show'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.rute.index'))->assertRedirect(route('login'));
        $this->get(route('petugas.rute.show'))->assertRedirect(route('login'));
    }

    public function test_invalid_coordinates_are_excluded_and_less_than_two_valid_points_shows_notice(): void
    {
        $today = now()->toDateString();

        // 1 titik valid
        $valid = $this->createScheduledPickup($this->officer1, $today, -7.8190240, 110.3705180, 'Prawirotaman');
        // Titik 0,0 -> dikecualikan
        $zeroZero = $this->createScheduledPickup($this->officer1, $today, 0.0, 0.0, 'Titik Nol Laut');
        // Titik di luar rentang Indonesia -> dikecualikan
        $outsideId = $this->createScheduledPickup($this->officer1, $today, 35.6895000, 139.6917000, 'Luar Indonesia');
        // Titik bukan angka (simulasi data kotor di DB SQLite) -> dikecualikan
        $nonNumeric = $this->createScheduledPickup($this->officer1, $today, -7.8000000, 110.3700000, 'Data Kotor');
        DB::table('pickup_requests')->where('id', $nonNumeric->id)->update(['latitude' => 'bukan-angka']);

        $response = $this->actingAs($this->admin)->get(route('admin.rute.index', [
            'date' => $today,
            'officer_id' => $this->officer1->id,
        ]));

        $response->assertOk()
            ->assertSee('Titik Dikecualikan dari Perhitungan')
            ->assertSee($zeroZero->code)
            ->assertSee($outsideId->code)
            ->assertSee($nonNumeric->code)
            ->assertSee('Optimasi tidak diperlukan');

        $calc = $response->viewData('calculation');
        $this->assertFalse($calc['requires_optimization']);
        $this->assertSame(1, $calc['valid_count']);
        $this->assertCount(3, $calc['excluded']);
        $this->assertSame($valid->id, $calc['ordered_stops'][0]['id']);
    }

    public function test_admin_can_calculate_and_save_route_sequence_and_recalculate(): void
    {
        $today = now()->toDateString();

        $p1 = $this->createScheduledPickup($this->officer1, $today, -7.8190240, 110.3705180, 'Jl. Prawirotaman');
        $p2 = $this->createScheduledPickup($this->officer1, $today, -7.8342100, 110.3548900, 'Jl. Bantul Km 4');
        $p3 = $this->createScheduledPickup($this->officer1, $today, -7.8021450, 110.3912800, 'Jl. Kusumanegara');
        $p4 = $this->createScheduledPickup($this->officer1, $today, -7.8076400, 110.3778100, 'Jl. Taman Siswa');
        $invalid = $this->createScheduledPickup($this->officer1, $today, 0.0, 0.0, 'Koordinat 0,0');

        // Hitung rute di halaman Admin
        $calcResp = $this->actingAs($this->admin)->get(route('admin.rute.index', [
            'date' => $today,
            'officer_id' => $this->officer1->id,
        ]));

        $calcResp->assertOk()
            ->assertSee('Urutan Awal')
            ->assertSee('Hasil Nearest Neighbor')
            ->assertSee('Hasil Nearest Neighbor + 2-Opt')
            ->assertSee('jarak garis lurus (Haversine)')
            ->assertSee('2-Opt tidak menjamin optimum global')
            ->assertSee('unpkg.com/leaflet', false);

        $calc = $calcResp->viewData('calculation');
        $this->assertTrue($calc['requires_optimization']);
        $this->assertLessThan($calc['initial_distance_km'], $calc['two_opt_distance_km']);

        // Simpan urutan rute
        $this->actingAs($this->admin)
            ->post(route('admin.rute.save'), [
                'date' => $today,
                'officer_id' => $this->officer1->id,
            ])
            ->assertRedirect(route('admin.rute.index', [
                'date' => $today,
                'officer_id' => $this->officer1->id,
            ]))
            ->assertSessionHas('success');

        $sequences = PickupRequest::whereIn('id', [$p1->id, $p2->id, $p3->id, $p4->id])
            ->pluck('route_sequence')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2, 3, 4], $sequences);
        $this->assertNull($invalid->fresh()->route_sequence);

        // Tambah titik ke-5 dan hitung ulang / simpan ulang kapan saja
        $p5 = $this->createScheduledPickup($this->officer1, $today, -7.7925760, 110.3658430, 'Jl. Malioboro');
        $this->actingAs($this->admin)
            ->post(route('admin.rute.save'), [
                'date' => $today,
                'officer_id' => $this->officer1->id,
            ])
            ->assertSessionHas('success');

        $updatedSequences = PickupRequest::whereIn('id', [$p1->id, $p2->id, $p3->id, $p4->id, $p5->id])
            ->pluck('route_sequence')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2, 3, 4, 5], $updatedSequences);
    }

    public function test_officer_sees_today_tasks_sorted_by_route_sequence_nulls_last_and_cannot_view_other_officer_route(): void
    {
        $today = now()->toDateString();

        $stopA = $this->createScheduledPickup($this->officer1, $today, -7.8190240, 110.3705180, 'Titik A');
        $stopB = $this->createScheduledPickup($this->officer1, $today, -7.8342100, 110.3548900, 'Titik B (Null)');
        $stopC = $this->createScheduledPickup($this->officer1, $today, -7.8076400, 110.3778100, 'Titik C');

        $stopA->route_sequence = 2;
        $stopA->save();
        $stopB->route_sequence = null;
        $stopB->save();
        $stopC->route_sequence = 1;
        $stopC->save();

        $this->createScheduledPickup($this->officer2, $today, -7.7630510, 110.3789120, 'Titik Petugas 2');

        $resp = $this->actingAs($this->officer1)->get(route('petugas.penjemputan.index'));
        $resp->assertOk()
            ->assertSee('officer-route-map', false)
            ->assertSee('Read-Only');

        $orderedCodes = $resp->viewData('pickups')->getCollection()->pluck('code')->all();
        // Urutan wajib: #1 (stopC), #2 (stopA), lalu null di akhir (stopB)
        $this->assertSame([$stopC->code, $stopA->code, $stopB->code], $orderedCodes);

        // Halaman rute khusus petugas miliknya sendiri -> 200 OK
        $this->actingAs($this->officer1)
            ->get(route('petugas.rute.show', $this->officer1))
            ->assertOk();

        // Petugas 1 mencoba melihat rute Petugas 2 -> 403 Forbidden
        $this->actingAs($this->officer1)
            ->get(route('petugas.rute.show', $this->officer2))
            ->assertForbidden();

        $this->actingAs($this->officer1)
            ->get(route('petugas.penjemputan.index', ['officer_id' => $this->officer2->id]))
            ->assertForbidden();
    }
}
