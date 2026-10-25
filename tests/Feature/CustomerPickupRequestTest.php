<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-07: Pengajuan Penjemputan oleh Nasabah.
 */
class CustomerPickupRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $userA;
    private Customer $customerA;
    private User $userB;
    private Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->petugas = User::create([
            'name' => 'Petugas Satu', 'email' => 'petugas@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);

        $this->userA = User::create([
            'name' => 'Nasabah Andi', 'email' => 'andi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerA = $this->userA->customer()->create([
            'name' => 'Nasabah Andi',
            'address' => 'Jl. Kaliurang Km 5, Sleman, Yogyakarta',
            'latitude' => -7.7630510,
            'longitude' => 110.3789120,
        ]);

        $this->userB = User::create([
            'name' => 'Nasabah Bela', 'email' => 'bela@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerB = $this->userB->customer()->create([
            'name' => 'Nasabah Bela',
            'address' => 'Jl. Prawirotaman No. 8, Yogyakarta',
            'latitude' => -7.8190240,
            'longitude' => 110.3705180,
        ]);
    }

    public function test_create_form_prefills_profile_address_and_coordinates_with_leaflet(): void
    {
        $this->actingAs($this->userA)
            ->get(route('nasabah.penjemputan.create'))
            ->assertOk()
            ->assertSee('Jl. Kaliurang Km 5, Sleman, Yogyakarta')
            ->assertSee('-7.7630510')
            ->assertSee('110.3789120')
            ->assertSee('unpkg.com/leaflet', false);
    }

    public function test_customer_can_submit_pickup_and_initial_status_is_always_menunggu(): void
    {
        $targetDate = now()->addDay()->toDateString();

        $response = $this->actingAs($this->userA)->post(route('nasabah.penjemputan.store'), [
            'requested_date' => $targetDate,
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
            'latitude' => '-7.7925760',
            'longitude' => '110.3658430',
            'notes' => 'Kardus 5 kg, Plastik PET 3 kg',
            // Upaya manipulasi dari browser yang WAJIB diabaikan
            'status' => 'selesai',
            'officer_id' => $this->petugas->id,
            'scheduled_by' => $this->admin->id,
            'customer_id' => $this->customerB->id,
        ]);

        $pickup = PickupRequest::first();
        $this->assertNotNull($pickup);

        $response->assertRedirect(route('nasabah.penjemputan.show', $pickup));
        $response->assertSessionHas('success');

        $this->assertSame($this->customerA->id, $pickup->customer_id);
        $this->assertSame(PickupRequest::STATUS_MENUNGGU, $pickup->status);
        $this->assertNull($pickup->officer_id);
        $this->assertNull($pickup->scheduled_by);
        $this->assertSame($targetDate, $pickup->requested_date->toDateString());
        $this->assertEquals(-7.7925760, (float) $pickup->latitude);
        $this->assertEquals(110.3658430, (float) $pickup->longitude);
        $this->assertSame('JMP-0001', $pickup->code);

        $this->actingAs($this->userA)
            ->get(route('nasabah.penjemputan.show', $pickup))
            ->assertOk()
            ->assertSee('JMP-0001')
            ->assertSee('Menunggu')
            ->assertSee('Kardus 5 kg, Plastik PET 3 kg');
    }

    public function test_validation_rejects_past_date_missing_fields_and_coordinates_outside_indonesia(): void
    {
        // Tanggal lampau ditolak
        $this->actingAs($this->userA)->post(route('nasabah.penjemputan.store'), [
            'requested_date' => now()->subDay()->toDateString(),
            'address' => 'Jl. Malioboro',
            'latitude' => '-7.7925760',
            'longitude' => '110.3658430',
        ])->assertSessionHasErrors('requested_date');

        // Koordinat kosong ditolak (wajib)
        $this->actingAs($this->userA)->post(route('nasabah.penjemputan.store'), [
            'requested_date' => now()->toDateString(),
            'address' => 'Jl. Malioboro',
            'latitude' => '',
            'longitude' => '',
        ])->assertSessionHasErrors(['latitude', 'longitude']);

        // Koordinat di luar rentang Indonesia (lat -11..6, lng 95..141) ditolak
        $invalidCoords = [
            ['lat' => '-12.5000000', 'lng' => '110.3700000', 'field' => 'latitude'],  // < -11
            ['lat' => '7.2000000',   'lng' => '110.3700000', 'field' => 'latitude'],  // > 6
            ['lat' => '-7.7900000',  'lng' => '94.5000000',  'field' => 'longitude'], // < 95
            ['lat' => '-7.7900000',  'lng' => '142.1000000', 'field' => 'longitude'], // > 141
        ];

        foreach ($invalidCoords as $case) {
            $this->actingAs($this->userA)->post(route('nasabah.penjemputan.store'), [
                'requested_date' => now()->toDateString(),
                'address' => 'Jl. Malioboro',
                'latitude' => $case['lat'],
                'longitude' => $case['lng'],
            ])->assertSessionHasErrors($case['field']);
        }

        $this->assertSame(0, PickupRequest::count());
    }

    public function test_customer_a_cannot_see_or_open_customer_b_pickup_requests(): void
    {
        $this->actingAs($this->userA)->post(route('nasabah.penjemputan.store'), [
            'requested_date' => now()->toDateString(),
            'address' => 'Alamat Andi',
            'latitude' => '-7.7630510',
            'longitude' => '110.3789120',
        ]);
        $pickupA = PickupRequest::where('customer_id', $this->customerA->id)->firstOrFail();

        $this->actingAs($this->userB)->post(route('nasabah.penjemputan.store'), [
            'requested_date' => now()->toDateString(),
            'address' => 'Alamat Bela Rahasia',
            'latitude' => '-7.8190240',
            'longitude' => '110.3705180',
        ]);
        $pickupB = PickupRequest::where('customer_id', $this->customerB->id)->firstOrFail();

        // Daftar milik A hanya menampilkan JMP milik A, tidak menampilkan milik B
        $this->actingAs($this->userA)
            ->get(route('nasabah.penjemputan.index'))
            ->assertOk()
            ->assertSee($pickupA->code)
            ->assertDontSee($pickupB->code)
            ->assertDontSee('Alamat Bela Rahasia');

        // Akses detail milik B ditolak 403
        $this->actingAs($this->userA)
            ->get(route('nasabah.penjemputan.show', $pickupB))
            ->assertForbidden();
    }

    public function test_non_nasabah_gets_403_and_guest_redirects_to_login(): void
    {
        $this->actingAs($this->admin)->get(route('nasabah.penjemputan.index'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('nasabah.penjemputan.create'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('nasabah.penjemputan.index'))->assertRedirect(route('login'));
        $this->post(route('nasabah.penjemputan.store'), [])->assertRedirect(route('login'));
    }
}
