<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Services\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-09: Pencatatan Hasil Penjemputan oleh Petugas -> Setoran Otomatis.
 */
class OfficerPickupCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer1;
    private User $officer2;
    private User $nasabahUser;
    private Customer $customer;
    private WasteType $pet;
    private WasteType $kardus;
    private WasteType $kaleng;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->officer1 = User::create([
            'name' => 'Petugas Rian', 'email' => 'rian@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->officer2 = User::create([
            'name' => 'Petugas Dedi', 'email' => 'dedi@test.com', 'password' => 'password', 'role' => 'petugas',
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

        $this->pet = WasteType::create(['name' => 'Plastik PET', 'unit' => 'kg', 'price_per_unit' => 3500.00]);
        $this->kardus = WasteType::create(['name' => 'Kardus', 'unit' => 'kg', 'price_per_unit' => 2000.00]);
        $this->kaleng = WasteType::create(['name' => 'Kaleng', 'unit' => 'kg', 'price_per_unit' => 4000.00]);
    }

    private function createScheduledPickup(User $officer, ?string $scheduledDate = null): PickupRequest
    {
        $service = app(PickupService::class);
        $pickup = $service->submitRequest($this->customer, [
            'requested_date' => now()->toDateString(),
            'address' => $this->customer->address,
            'latitude' => -7.7925760,
            'longitude' => 110.3658430,
            'notes' => 'Perkiraan 5 kg campuran',
        ]);

        return $service->schedule(
            pickupRequest: $pickup,
            scheduledDate: $scheduledDate ?? now()->toDateString(),
            officerId: $officer->id,
            admin: $this->admin,
        );
    }

    public function test_officer_sees_only_own_scheduled_tasks_sorted_by_date_with_map_links(): void
    {
        $later = $this->createScheduledPickup($this->officer1, now()->addDays(2)->toDateString());
        $sooner = $this->createScheduledPickup($this->officer1, now()->toDateString());
        $otherOfficerTask = $this->createScheduledPickup($this->officer2, now()->toDateString());

        $response = $this->actingAs($this->officer1)->get(route('petugas.penjemputan.index'));
        $response->assertOk();

        $listed = $response->viewData('pickups')->getCollection()->pluck('code')->all();
        $this->assertSame([$sooner->code, $later->code], $listed);
        $this->assertNotContains($otherOfficerTask->code, $listed);

        $this->actingAs($this->officer1)
            ->get(route('petugas.penjemputan.show', $sooner))
            ->assertOk()
            ->assertSee('google.com/maps', false)
            ->assertSee('openstreetmap.org', false);
    }

    public function test_completing_pickup_creates_deposits_updates_balance_and_shows_cross_links(): void
    {
        $pickup = $this->createScheduledPickup($this->officer1);

        // Baris 1: 2.500 kg Plastik PET x Rp 3.500 = Rp 8.750,00
        // Baris 2: 3.250 kg Kardus x Rp 2.000      = Rp 6.500,00
        // Total kenaikan saldo                     = Rp 15.250,00
        $response = $this->actingAs($this->officer1)->post(route('petugas.penjemputan.complete', $pickup), [
            'items' => [
                ['waste_type_id' => $this->pet->id, 'weight' => '2.500'],
                ['waste_type_id' => $this->kardus->id, 'weight' => '3.250'],
            ],
        ]);

        $response->assertRedirect(route('petugas.penjemputan.show', $pickup));
        $response->assertSessionHas('success');

        $pickup->refresh();
        $this->assertSame(PickupRequest::STATUS_SELESAI, $pickup->status);
        $this->assertNotNull($pickup->completed_at);

        $deposits = Deposit::orderBy('id')->get();
        $this->assertCount(2, $deposits);
        foreach ($deposits as $d) {
            $this->assertSame($pickup->id, $d->pickup_request_id);
            $this->assertSame($this->officer1->id, $d->officer_id);
            $this->assertSame($this->customer->id, $d->customer_id);
        }

        $this->assertEquals(15250.00, (float) $this->customer->fresh()->balance);

        // Riwayat setoran & bukti setoran menampilkan "Dari penjemputan JMP-xxxx"
        $firstDeposit = $deposits->first();
        $this->actingAs($this->officer1)
            ->get(route('petugas.setoran.index'))
            ->assertOk()
            ->assertSee("Dari penjemputan {$pickup->code}");

        $this->actingAs($this->officer1)
            ->get(route('petugas.setoran.show', $firstDeposit))
            ->assertOk()
            ->assertSee("Dari penjemputan {$pickup->code}");

        // Detail penjemputan di Admin & Nasabah menampilkan daftar bukti setoran
        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.show', $pickup))
            ->assertOk()
            ->assertSee($deposits[0]->code)
            ->assertSee($deposits[1]->code);

        $this->actingAs($this->nasabahUser)
            ->get(route('nasabah.penjemputan.show', $pickup))
            ->assertOk()
            ->assertSee($deposits[0]->code)
            ->assertSee($deposits[1]->code);

        // Nasabah melihat setoran hasil penjemputan di riwayat gabungannya
        $this->actingAs($this->nasabahUser)
            ->get(route('nasabah.riwayat.index'))
            ->assertOk()
            ->assertSee($deposits[0]->code)
            ->assertSee($deposits[1]->code)
            ->assertSee("Dari penjemputan {$pickup->code}");
    }

    public function test_entire_transaction_rolls_back_if_any_single_row_is_invalid(): void
    {
        $pickup = $this->createScheduledPickup($this->officer1);

        // Nonaktifkan Kaleng sehingga baris ke-2 gagal di DepositService setelah baris ke-1 dieksekusi
        $this->kaleng->is_active = false;
        $this->kaleng->save();

        $response = $this->actingAs($this->officer1)->post(route('petugas.penjemputan.complete', $pickup), [
            'items' => [
                ['waste_type_id' => $this->pet->id, 'weight' => '4.000'],    // baris valid
                ['waste_type_id' => $this->kaleng->id, 'weight' => '1.500'], // baris nonaktif -> gagal
            ],
        ]);

        $response->assertSessionHasErrors('waste_type_id');

        // Rollback total: 0 Deposit, saldo tetap 0, status penjemputan tetap 'terjadwal'
        $this->assertSame(0, Deposit::count());
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);
        $pickup->refresh();
        $this->assertSame(PickupRequest::STATUS_TERJADWAL, $pickup->status);
        $this->assertNull($pickup->completed_at);
    }

    public function test_duplicate_waste_type_and_double_completion_are_rejected(): void
    {
        $pickup = $this->createScheduledPickup($this->officer1);

        // Jenis sampah dobel ditolak
        $this->actingAs($this->officer1)->post(route('petugas.penjemputan.complete', $pickup), [
            'items' => [
                ['waste_type_id' => $this->pet->id, 'weight' => '1.000'],
                ['waste_type_id' => $this->pet->id, 'weight' => '2.000'],
            ],
        ])->assertSessionHasErrors('items.0.waste_type_id');

        $this->assertSame(0, Deposit::count());

        // Selesaikan pertama kali -> sukses
        $this->actingAs($this->officer1)->post(route('petugas.penjemputan.complete', $pickup), [
            'items' => [
                ['waste_type_id' => $this->pet->id, 'weight' => '2.000'],
            ],
        ])->assertRedirect(route('petugas.penjemputan.show', $pickup));

        $this->assertSame(1, Deposit::count());
        $this->assertEquals(7000.00, (float) $this->customer->fresh()->balance);

        // Percobaan penyelesaian kedua kali -> ditolak, tidak menambah setoran/saldo
        $this->actingAs($this->officer1)->post(route('petugas.penjemputan.complete', $pickup), [
            'items' => [
                ['waste_type_id' => $this->kardus->id, 'weight' => '3.000'],
            ],
        ])->assertSessionHasErrors('status');

        $this->assertSame(1, Deposit::count());
        $this->assertEquals(7000.00, (float) $this->customer->fresh()->balance);
    }

    public function test_other_officer_gets_403_on_view_and_complete(): void
    {
        $pickup = $this->createScheduledPickup($this->officer1);

        $this->actingAs($this->officer2)
            ->get(route('petugas.penjemputan.show', $pickup))
            ->assertForbidden();

        $this->actingAs($this->officer2)
            ->post(route('petugas.penjemputan.complete', $pickup), [
                'items' => [
                    ['waste_type_id' => $this->pet->id, 'weight' => '2.000'],
                ],
            ])
            ->assertForbidden();

        $this->assertSame(0, Deposit::count());
        $this->assertSame(PickupRequest::STATUS_TERJADWAL, $pickup->fresh()->status);
    }
}
