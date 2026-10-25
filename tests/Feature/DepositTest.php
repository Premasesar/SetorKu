<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WasteType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-04: Pencatatan Setoran & Penambahan Saldo Otomatis.
 */
class DepositTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabahUser;
    private Customer $customer;
    private WasteType $wasteType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->petugas = User::create([
            'name' => 'Petugas Satu', 'email' => 'petugas@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->nasabahUser = User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);

        $this->customer = $this->nasabahUser->customer()->create([
            'name' => 'Budi Santoso',
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
        ]);

        $this->wasteType = WasteType::create([
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 3500.00,
        ]);
    }

    public function test_petugas_can_record_deposit_and_balance_increases_accurately(): void
    {
        // 2.355 kg x Rp 3.500,00 = Rp 8.242,50
        $response = $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '2.355',
            // Nilai palsu dari browser harus diabaikan sepenuhnya oleh backend
            'price_per_unit' => 999999,
            'total_value' => 99999999,
            'officer_id' => $this->admin->id,
        ]);

        $deposit = Deposit::first();
        $this->assertNotNull($deposit);

        $response->assertRedirect(route('petugas.setoran.show', $deposit));
        $response->assertSessionHas('success');

        $this->assertSame($this->customer->id, $deposit->customer_id);
        $this->assertSame($this->wasteType->id, $deposit->waste_type_id);
        $this->assertSame($this->petugas->id, $deposit->officer_id);
        $this->assertEquals(2.355, (float) $deposit->weight);
        $this->assertEquals(3500.00, (float) $deposit->price_per_unit);
        $this->assertEquals(8242.50, (float) $deposit->total_value);

        // Saldo nasabah bertambah sesuai hitungan manual
        $this->assertEquals(8242.50, (float) $this->customer->fresh()->balance);

        // Setoran kedua terakumulasi ke saldo
        $kardus = WasteType::create(['name' => 'Kardus', 'unit' => 'kg', 'price_per_unit' => 2000.00]);
        $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $kardus->id,
            'weight' => '1.500', // 1.5 x 2000 = 3000.00
        ]);

        $this->assertEquals(11242.50, (float) $this->customer->fresh()->balance);

        // Halaman bukti setoran dapat dibuka dan menampilkan snapshot & petugas
        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.show', $deposit))
            ->assertOk()
            ->assertSee('Bukti Setoran Sampah')
            ->assertSee($deposit->code)
            ->assertSee('Budi Santoso')
            ->assertSee('Petugas Satu')
            ->assertSee('8.242,50');
    }

    public function test_price_snapshot_and_balance_do_not_change_when_master_price_changes(): void
    {
        $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '4.000', // 4 x 3500 = 14000
        ]);

        $deposit = Deposit::first();
        $this->assertEquals(3500.00, (float) $deposit->price_per_unit);
        $this->assertEquals(14000.00, (float) $deposit->total_value);

        // Admin mengubah harga master Plastik PET menjadi Rp 6.000
        $this->actingAs($this->admin)->put(route('admin.jenis-sampah.update', $this->wasteType), [
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 6000.00,
        ])->assertRedirect(route('admin.jenis-sampah.index'));

        // Snapshot setoran lama & saldo lama TIDAK boleh berubah
        $deposit->refresh();
        $this->assertEquals(3500.00, (float) $deposit->price_per_unit);
        $this->assertEquals(14000.00, (float) $deposit->total_value);
        $this->assertEquals(14000.00, (float) $this->customer->fresh()->balance);

        // Setoran baru memakai harga baru Rp 6.000
        $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '2.000', // 2 x 6000 = 12000
        ]);

        $this->assertEquals(26000.00, (float) $this->customer->fresh()->balance);
    }

    public function test_empty_zero_or_negative_weight_is_rejected(): void
    {
        foreach (['', '0', '0.000', '-1.5', 'abc'] as $invalidWeight) {
            $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
                'customer_id' => $this->customer->id,
                'waste_type_id' => $this->wasteType->id,
                'weight' => $invalidWeight,
            ])->assertSessionHasErrors('weight');
        }

        $this->assertSame(0, Deposit::count());
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);
    }

    public function test_inactive_customer_or_inactive_waste_type_is_rejected(): void
    {
        // Nasabah nonaktif ditolak
        $this->customer->is_active = false;
        $this->customer->save();

        $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '2.000',
        ])->assertSessionHasErrors('customer_id');

        // Aktifkan nasabah kembali, lalu nonaktifkan jenis sampah
        $this->customer->is_active = true;
        $this->customer->save();
        $this->wasteType->is_active = false;
        $this->wasteType->save();

        $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '2.000',
        ])->assertSessionHasErrors('waste_type_id');

        $this->assertSame(0, Deposit::count());
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);
    }

    public function test_petugas_can_search_customer_by_name_or_code_on_create_form(): void
    {
        $other = Customer::factory()->create(['name' => 'Rina Kusuma']);

        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create', ['q' => $this->customer->code]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Rina Kusuma');

        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create', ['q' => 'Rina']))
            ->assertOk()
            ->assertSee('Rina Kusuma')
            ->assertDontSee('Budi Santoso');
    }

    public function test_admin_can_only_view_history_and_cannot_record_deposit(): void
    {
        $this->actingAs($this->petugas)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '3.000',
        ]);
        $deposit = Deposit::first();

        // Admin boleh melihat riwayat & bukti
        $this->actingAs($this->admin)
            ->get(route('admin.setoran.index'))
            ->assertOk()
            ->assertSee($deposit->code)
            ->assertSee('Budi Santoso');

        $this->actingAs($this->admin)
            ->get(route('admin.setoran.show', $deposit))
            ->assertOk()
            ->assertSee('Bukti Setoran Sampah');

        // Admin TIDAK boleh mencatat setoran
        $this->actingAs($this->admin)->get(route('petugas.setoran.create'))->assertForbidden();
        $this->actingAs($this->admin)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '1.000',
        ])->assertForbidden();
        $this->actingAs($this->admin)->post('/admin/setoran', [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '1.000',
        ])->assertStatus(405);

        $this->assertSame(1, Deposit::count());
    }

    public function test_nasabah_and_guest_cannot_record_or_access_officer_deposits(): void
    {
        $this->actingAs($this->nasabahUser)->get(route('petugas.setoran.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('petugas.setoran.create'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '5.000',
        ])->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.setoran.index'))->assertForbidden();

        $this->assertSame(0, Deposit::count());
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('petugas.setoran.create'))->assertRedirect(route('login'));
        $this->post(route('petugas.setoran.store'), [
            'customer_id' => $this->customer->id,
            'waste_type_id' => $this->wasteType->id,
            'weight' => '1.000',
        ])->assertRedirect(route('login'));
        $this->get(route('admin.setoran.index'))->assertRedirect(route('login'));
    }
}
