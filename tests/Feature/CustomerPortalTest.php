<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WasteType;
use App\Models\Withdrawal;
use App\Services\DepositService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature Test PBI-06: Portal Nasabah (Dashboard, Riwayat Gabungan, Profil Mandiri, Isolasi Akses).
 */
class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $userA;
    private Customer $customerA;
    private User $userB;
    private Customer $customerB;
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

        $this->userA = User::create([
            'name' => 'Nasabah Andi', 'email' => 'andi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerA = $this->userA->customer()->create([
            'name' => 'Nasabah Andi',
            'address' => 'Jl. Kaliurang Km 5, Yogyakarta',
            'phone' => '081234567890',
            'latitude' => -7.7654321,
            'longitude' => 110.3789012,
        ]);

        $this->userB = User::create([
            'name' => 'Nasabah Bela', 'email' => 'bela@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerB = $this->userB->customer()->create([
            'name' => 'Nasabah Bela',
            'address' => 'Jl. Parangtritis Km 3, Yogyakarta',
            'phone' => '089876543210',
        ]);

        $this->wasteType = WasteType::create([
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 4000.00,
        ]);
    }

    public function test_dashboard_shows_own_balance_and_at_most_five_latest_transactions(): void
    {
        $depositService = app(DepositService::class);
        $withdrawalService = app(WithdrawalService::class);

        $baseTime = Carbon::parse('2026-10-20 08:00:00');
        $createdCodes = [];

        // Buat 5 setoran dan 2 penarikan (total 7 transaksi) dengan waktu berurutan
        for ($i = 1; $i <= 5; $i++) {
            Carbon::setTestNow($baseTime->copy()->addMinutes($i * 10));
            $d = $depositService->recordDeposit($this->customerA->id, $this->wasteType->id, 2.000, $this->petugas);
            $createdCodes[] = $d->code;
        }

        for ($j = 1; $j <= 2; $j++) {
            Carbon::setTestNow($baseTime->copy()->addMinutes(50 + $j * 10));
            $w = $withdrawalService->recordWithdrawal($this->customerA->id, 5000.00, $this->petugas);
            $createdCodes[] = $w->code;
        }
        Carbon::setTestNow();

        // Saldo akhir: (5 x 8.000) - (2 x 5.000) = 40.000 - 10.000 = Rp 30.000,00
        $response = $this->actingAs($this->userA)->get(route('nasabah.dashboard'));

        $response->assertOk()
            ->assertSee('Portal Nasabah')
            ->assertSee('30.000,00');

        $recent = $response->viewData('recentTransactions');
        $this->assertCount(5, $recent);

        // Urutan harus terbaru ke terlama: transaksi ke-7, ke-6, ke-5, ke-4, ke-3
        $expectedLatestFive = array_reverse(array_slice($createdCodes, 2, 5));
        $actualCodes = $recent->pluck('code')->all();
        $this->assertSame($expectedLatestFive, $actualCodes);

        // 2 transaksi paling lama tidak tampil di widget 5 terakhir dashboard
        $this->assertNotContains($createdCodes[0], $actualCodes);
        $this->assertNotContains($createdCodes[1], $actualCodes);
    }

    public function test_combined_transaction_history_is_sorted_by_time_and_paginated(): void
    {
        $depositService = app(DepositService::class);
        $withdrawalService = app(WithdrawalService::class);

        $baseTime = Carbon::parse('2026-10-21 09:00:00');
        $expectedChronological = [];

        // Selang-seling 8 setoran dan 4 penarikan = 12 transaksi
        for ($i = 1; $i <= 8; $i++) {
            Carbon::setTestNow($baseTime->copy()->addMinutes($i * 20));
            $d = $depositService->recordDeposit($this->customerA->id, $this->wasteType->id, 3.000, $this->petugas);
            $expectedChronological[] = $d->code;

            if ($i % 2 === 0) {
                Carbon::setTestNow($baseTime->copy()->addMinutes($i * 20 + 5));
                $w = $withdrawalService->recordWithdrawal($this->customerA->id, 4000.00, $this->petugas);
                $expectedChronological[] = $w->code;
            }
        }
        Carbon::setTestNow();

        $expectedDesc = array_reverse($expectedChronological);

        $page1 = $this->actingAs($this->userA)->get(route('nasabah.riwayat.index'));
        $page1->assertOk();
        $txPage1 = $page1->viewData('transactions');
        $this->assertSame(12, $txPage1->total());
        $this->assertCount(10, $txPage1);
        $this->assertSame(array_slice($expectedDesc, 0, 10), $txPage1->getCollection()->pluck('code')->all());

        $page2 = $this->actingAs($this->userA)->get(route('nasabah.riwayat.index', ['page' => 2]));
        $page2->assertOk();
        $txPage2 = $page2->viewData('transactions');
        $this->assertCount(2, $txPage2);
        $this->assertSame(array_slice($expectedDesc, 10, 2), $txPage2->getCollection()->pluck('code')->all());
    }

    public function test_customer_can_update_own_profile_and_balance_code_role_inputs_are_ignored(): void
    {
        app(DepositService::class)->recordDeposit($this->customerA->id, $this->wasteType->id, 5.000, $this->petugas); // Rp 20.000
        $originalCode = $this->customerA->code;

        $this->actingAs($this->userA)
            ->get(route('nasabah.profil.edit'))
            ->assertOk()
            ->assertDontSee('name="balance"', false)
            ->assertDontSee('name="role"', false)
            ->assertDontSee('name="code"', false);

        $response = $this->actingAs($this->userA)->put(route('nasabah.profil.update'), [
            'name' => 'Andi Wijaya Baru',
            'address' => 'Jl. Gejayan No. 88, Sleman',
            'phone' => '081122334455',
            'latitude' => '-7.7720000',
            'longitude' => '110.3890000',
            // Upaya manipulasi dari browser yang WAJIB diabaikan
            'balance' => 99999999.99,
            'code' => 'NSB-9999',
            'id' => 9999,
            'role' => 'admin',
            'is_active' => 0,
            'customer_id' => $this->customerB->id,
            'user_id' => $this->admin->id,
        ]);

        $response->assertRedirect(route('nasabah.profil.edit'));
        $response->assertSessionHas('success');

        $this->customerA->refresh();
        $this->userA->refresh();
        $this->customerB->refresh();

        $this->assertSame('Andi Wijaya Baru', $this->customerA->name);
        $this->assertSame('Andi Wijaya Baru', $this->userA->name);
        $this->assertSame('Jl. Gejayan No. 88, Sleman', $this->customerA->address);
        $this->assertSame('081122334455', $this->customerA->phone);
        $this->assertEquals(-7.7720000, (float) $this->customerA->latitude);
        $this->assertEquals(110.3890000, (float) $this->customerA->longitude);

        // Saldo, kode, peran, dan status aktif TIDAK berubah
        $this->assertEquals(20000.00, (float) $this->customerA->balance);
        $this->assertSame($originalCode, $this->customerA->code);
        $this->assertSame('nasabah', $this->userA->role);
        $this->assertTrue($this->customerA->is_active);

        // Data nasabah B tidak tersentuh
        $this->assertSame('Nasabah Bela', $this->customerB->name);
    }

    public function test_customer_a_cannot_view_or_access_customer_b_data(): void
    {
        $depositService = app(DepositService::class);
        $withdrawalService = app(WithdrawalService::class);

        $depositA = $depositService->recordDeposit($this->customerA->id, $this->wasteType->id, 2.000, $this->petugas);
        $depositB = $depositService->recordDeposit($this->customerB->id, $this->wasteType->id, 10.000, $this->petugas);
        $withdrawalB = $withdrawalService->recordWithdrawal($this->customerB->id, 12000.00, $this->petugas);

        // Dashboard A hanya menampilkan data A, tidak menampilkan kode/saldo B
        $this->actingAs($this->userA)
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee($depositA->code)
            ->assertDontSee($depositB->code)
            ->assertDontSee($withdrawalB->code)
            ->assertDontSee('Nasabah Bela');

        // Riwayat A tidak memuat transaksi milik B
        $this->actingAs($this->userA)
            ->get(route('nasabah.riwayat.index'))
            ->assertOk()
            ->assertSee($depositA->code)
            ->assertDontSee($depositB->code)
            ->assertDontSee($withdrawalB->code);

        // A boleh melihat bukti transaksinya sendiri
        $this->actingAs($this->userA)
            ->get(route('nasabah.riwayat.deposit', $depositA))
            ->assertOk()
            ->assertSee($depositA->code);

        // A DITOLAK (403) saat mencoba membuka bukti setoran atau penarikan milik B
        $this->actingAs($this->userA)
            ->get(route('nasabah.riwayat.deposit', $depositB))
            ->assertForbidden();

        $this->actingAs($this->userA)
            ->get(route('nasabah.riwayat.withdrawal', $withdrawalB))
            ->assertForbidden();

        // A juga DITOLAK (403) membuka detail B di rute admin/petugas
        $this->actingAs($this->userA)
            ->get(route('admin.nasabah.show', $this->customerB))
            ->assertForbidden();
        $this->actingAs($this->userA)
            ->get(route('petugas.nasabah.show', $this->customerB))
            ->assertForbidden();
    }

    public function test_non_nasabah_cannot_access_customer_portal(): void
    {
        $this->actingAs($this->admin)->get(route('nasabah.riwayat.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('nasabah.profil.edit'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('nasabah.riwayat.index'))->assertForbidden();
        $this->actingAs($this->petugas)->put(route('nasabah.profil.update'), [
            'name' => 'Hack',
            'address' => 'Hack',
        ])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('nasabah.riwayat.index'))->assertRedirect(route('login'));
        $this->get(route('nasabah.profil.edit'))->assertRedirect(route('login'));
    }
}
