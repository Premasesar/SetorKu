<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\WasteType;
use App\Services\DepositService;
use App\Services\PickupService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiBatch3SmokeTest extends TestCase
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
            'name' => 'Admin Utama',
            'email' => 'admin@setorku.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->petugas = User::create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas@setorku.test',
            'password' => 'password123',
            'role' => 'petugas',
        ]);

        $this->nasabahUser = User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@setorku.test',
            'password' => 'password123',
            'role' => 'nasabah',
        ]);

        $this->customer = $this->nasabahUser->customer()->create([
            'name' => 'Siti Rahayu',
            'address' => 'Jl. Kaliurang KM 5, Sleman',
            'latitude' => -7.7654321,
            'longitude' => 110.3789123,
        ]);

        $this->wasteType = WasteType::create([
            'name' => 'Kardus Bekas',
            'unit' => 'kg',
            'price_per_unit' => 3000.00,
        ]);
    }

    public function test_deposits_index_renders_ui_staff_with_all_columns_and_role_permissions(): void
    {
        $pickupService = app(PickupService::class);
        $pickup = $pickupService->submitRequest($this->customer, [
            'requested_date' => now()->toDateString(),
            'address' => $this->customer->address,
            'latitude' => $this->customer->latitude,
            'longitude' => $this->customer->longitude,
            'notes' => 'Kardus menumpuk',
        ]);
        $pickup = $pickupService->schedule(
            pickupRequest: $pickup,
            scheduledDate: now()->toDateString(),
            officerId: $this->petugas->id,
            admin: $this->admin,
        );
        $pickupService->completePickup($pickup, [
            ['waste_type_id' => $this->wasteType->id, 'weight' => 4.500],
        ], $this->petugas);

        // Petugas melihat tombol Catat Setoran Baru + 9 kolom + pill Dari penjemputan JMP-...
        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Riwayat Setoran')
            ->assertSee('Catat Setoran Baru')
            ->assertSee('Cari nama/kode nasabah, kode setoran, atau jenis sampah...', false)
            ->assertSee('Dari penjemputan ' . $pickup->code)
            ->assertSee('Kardus Bekas')
            ->assertSee('Bukti');

        // Admin hanya melihat riwayat tanpa tombol Catat Setoran Baru
        $this->actingAs($this->admin)
            ->get(route('admin.setoran.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Riwayat Setoran')
            ->assertDontSee(route('petugas.setoran.create'), false)
            ->assertSee('Dari penjemputan ' . $pickup->code);

        // Nasabah ditolak 403, Guest diarahkan ke login 302
        $this->actingAs($this->nasabahUser)->get(route('petugas.setoran.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.setoran.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('petugas.setoran.index'))->assertRedirect(route('login'));
    }

    public function test_deposits_create_renders_ui_staff_with_qr_camera_and_auto_calc_elements(): void
    {
        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Catat Setoran Sampah')
            ->assertSee('qr-camera-reader', false)
            ->assertSee('Scan dengan kamera')
            ->assertSee('Tutup Kamera')
            ->assertSee('name="customer_id"', false)
            ->assertSee('name="waste_type_id"', false)
            ->assertSee('name="weight"', false)
            ->assertSee('step="0.001"', false)
            ->assertSee('min="0.001"', false)
            ->assertSee('estimated-total', false)
            ->assertSee('Simpan Setoran')
            ->assertSee('Batal');

        $this->actingAs($this->admin)->get(route('petugas.setoran.create'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('petugas.setoran.create'))->assertForbidden();
    }

    public function test_deposits_show_renders_ui_staff_receipt_for_petugas_and_admin(): void
    {
        $deposit = app(DepositService::class)->recordDeposit(
            customerId: $this->customer->id,
            wasteTypeId: $this->wasteType->id,
            weight: 3.250,
            officer: $this->petugas,
        );

        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.show', $deposit))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Bukti Setoran Sampah — SetorKu')
            ->assertSee($deposit->code)
            ->assertSee('Siti Rahayu')
            ->assertSee('Kardus Bekas')
            ->assertSee('3,250')
            ->assertSee('3.000,00')
            ->assertSee('9.750,00')
            ->assertSee('Petugas Lapangan')
            ->assertSee('Cetak Bukti')
            ->assertSee('Catat Setoran Baru');

        $this->actingAs($this->admin)
            ->get(route('admin.setoran.show', $deposit))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($deposit->code)
            ->assertDontSee('Catat Setoran Baru');

        $this->actingAs($this->nasabahUser)->get(route('petugas.setoran.show', $deposit))->assertForbidden();
    }

    public function test_withdrawals_index_create_and_show_render_ui_staff_with_all_elements(): void
    {
        app(DepositService::class)->recordDeposit(
            customerId: $this->customer->id,
            wasteTypeId: $this->wasteType->id,
            weight: 10.000,
            officer: $this->petugas,
        );

        $withdrawal = app(WithdrawalService::class)->recordWithdrawal(
            customerId: $this->customer->id,
            amount: 12000.00,
            officer: $this->petugas,
        );

        // 1. Index (Petugas & Admin)
        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Riwayat Penarikan Saldo')
            ->assertSee('Catat Penarikan Baru')
            ->assertSee('Cari nama/kode nasabah atau kode penarikan...', false)
            ->assertSee($withdrawal->code)
            ->assertSee('text-danger', false)
            ->assertSee('12.000,00')
            ->assertSee('Bukti');

        $this->actingAs($this->admin)
            ->get(route('admin.penarikan.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($withdrawal->code)
            ->assertDontSee(route('petugas.penarikan.create'), false);

        // 2. Create (Petugas only)
        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.create'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Catat Penarikan Saldo')
            ->assertSee('qr-camera-reader', false)
            ->assertSee('name="customer_id"', false)
            ->assertSee('name="amount"', false)
            ->assertSee('remaining-balance', false)
            ->assertSee('Simpan Penarikan')
            ->assertSee('Batal');

        $this->actingAs($this->admin)->get(route('petugas.penarikan.create'))->assertForbidden();

        // 3. Show (Petugas & Admin)
        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.show', $withdrawal))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Bukti Penarikan Saldo — SetorKu')
            ->assertSee($withdrawal->code)
            ->assertSee('Siti Rahayu')
            ->assertSee('12.000,00')
            ->assertSee('18.000,00')
            ->assertSee('Petugas Lapangan')
            ->assertSee('Cetak Bukti')
            ->assertSee('Catat Penarikan Baru');

        $this->actingAs($this->admin)
            ->get(route('admin.penarikan.show', $withdrawal))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($withdrawal->code)
            ->assertDontSee('Catat Penarikan Baru');

        // Nasabah & Guest ditolak
        $this->actingAs($this->nasabahUser)->get(route('petugas.penarikan.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.penarikan.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('petugas.penarikan.index'))->assertRedirect(route('login'));
    }
}
