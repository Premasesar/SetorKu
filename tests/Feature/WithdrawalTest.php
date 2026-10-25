<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\WasteType;
use App\Models\Withdrawal;
use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-05: Penarikan Saldo Nasabah.
 */
class WithdrawalTest extends TestCase
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
            'price_per_unit' => 5000.00,
        ]);

        // Saldo awal Rp 25.000,00 melalui setoran resmi (5 kg x Rp 5.000)
        app(DepositService::class)->recordDeposit(
            customerId: $this->customer->id,
            wasteTypeId: $this->wasteType->id,
            weight: 5.000,
            officer: $this->petugas,
        );
        $this->customer->refresh();
    }

    public function test_petugas_can_record_partial_withdrawal_and_view_receipt(): void
    {
        $response = $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '10000.50',
            // Input palsu dari browser diabaikan
            'officer_id' => $this->admin->id,
            'balance' => 99999999,
        ]);

        $withdrawal = Withdrawal::first();
        $this->assertNotNull($withdrawal);

        $response->assertRedirect(route('petugas.penarikan.show', $withdrawal));
        $response->assertSessionHas('success');

        $this->assertSame($this->customer->id, $withdrawal->customer_id);
        $this->assertSame($this->petugas->id, $withdrawal->officer_id);
        $this->assertEquals(10000.50, (float) $withdrawal->amount);
        $this->assertEquals(14999.50, (float) $this->customer->fresh()->balance);

        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.show', $withdrawal))
            ->assertOk()
            ->assertSee('Bukti Penarikan Saldo')
            ->assertSee($withdrawal->code)
            ->assertSee('Budi Santoso')
            ->assertSee('Petugas Satu')
            ->assertSee('10.000,50');
    }

    public function test_withdrawal_equal_to_full_balance_leaves_zero_balance(): void
    {
        $this->assertEquals(25000.00, (float) $this->customer->balance);

        $response = $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '25000.00',
        ]);

        $withdrawal = Withdrawal::first();
        $this->assertNotNull($withdrawal);
        $response->assertRedirect(route('petugas.penarikan.show', $withdrawal));

        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);
    }

    public function test_withdrawal_exceeding_balance_is_rejected_and_balance_never_becomes_negative(): void
    {
        // Melebihi Rp 0,01 dari saldo Rp 25.000,00
        $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '25000.01',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Withdrawal::count());
        $this->assertEquals(25000.00, (float) $this->customer->fresh()->balance);

        // Tarik seluruh saldo hingga 0, lalu coba tarik Rp 1 lagi -> tetap ditolak & saldo tetap 0
        $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '25000.00',
        ]);
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);

        $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '1.00',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, Withdrawal::count());
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance);
        $this->assertGreaterThanOrEqual(0.00, (float) $this->customer->fresh()->balance);
    }

    public function test_empty_zero_negative_or_invalid_amount_is_rejected(): void
    {
        foreach (['', '0', '0.00', '-5000', 'abc'] as $invalidAmount) {
            $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
                'customer_id' => $this->customer->id,
                'amount' => $invalidAmount,
            ])->assertSessionHasErrors('amount');
        }

        $this->assertSame(0, Withdrawal::count());
        $this->assertEquals(25000.00, (float) $this->customer->fresh()->balance);
    }

    public function test_inactive_customer_is_rejected(): void
    {
        $this->customer->is_active = false;
        $this->customer->save();

        $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '5000.00',
        ])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Withdrawal::count());
        $this->assertEquals(25000.00, (float) $this->customer->fresh()->balance);
    }

    public function test_petugas_can_search_customer_by_name_or_code_on_create_form(): void
    {
        Customer::factory()->create(['name' => 'Rina Kusuma']);

        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.create', ['q' => $this->customer->code]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Rina Kusuma');

        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.create', ['q' => 'Rina']))
            ->assertOk()
            ->assertSee('Rina Kusuma')
            ->assertDontSee('Budi Santoso');
    }

    public function test_admin_can_only_view_history_and_cannot_record_withdrawal(): void
    {
        $this->actingAs($this->petugas)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '5000.00',
        ]);
        $withdrawal = Withdrawal::first();

        $this->actingAs($this->admin)
            ->get(route('admin.penarikan.index'))
            ->assertOk()
            ->assertSee($withdrawal->code)
            ->assertSee('Budi Santoso');

        $this->actingAs($this->admin)
            ->get(route('admin.penarikan.show', $withdrawal))
            ->assertOk()
            ->assertSee('Bukti Penarikan Saldo');

        $this->actingAs($this->admin)->get(route('petugas.penarikan.create'))->assertForbidden();
        $this->actingAs($this->admin)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '1000.00',
        ])->assertForbidden();
        $this->actingAs($this->admin)->post('/admin/penarikan', [
            'customer_id' => $this->customer->id,
            'amount' => '1000.00',
        ])->assertStatus(405);

        $this->assertSame(1, Withdrawal::count());
    }

    public function test_nasabah_and_guest_cannot_record_or_access_officer_withdrawals(): void
    {
        $this->actingAs($this->nasabahUser)->get(route('petugas.penarikan.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('petugas.penarikan.create'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '5000.00',
        ])->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.penarikan.index'))->assertForbidden();

        $this->assertSame(0, Withdrawal::count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('petugas.penarikan.create'))->assertRedirect(route('login'));
        $this->post(route('petugas.penarikan.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '5000.00',
        ])->assertRedirect(route('login'));
        $this->get(route('admin.penarikan.index'))->assertRedirect(route('login'));
    }
}
