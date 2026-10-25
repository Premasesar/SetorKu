<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WasteType;
use App\Services\DepositService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-13: Dashboard Dampak Lingkungan (Admin) & Kartu Kontribusi Saya (Nasabah).
 */
class ImpactDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabahUserA;
    private User $nasabahUserB;
    private Customer $customerA;
    private Customer $customerB;
    private WasteType $pet;
    private WasteType $kardus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->petugas = User::create([
            'name' => 'Petugas', 'email' => 'petugas@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);

        $this->nasabahUserA = User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerA = $this->nasabahUserA->customer()->create([
            'name' => 'Budi Santoso', 'address' => 'Jl. Malioboro, Yogyakarta',
        ]);

        $this->nasabahUserB = User::create([
            'name' => 'Siti Aminah', 'email' => 'siti@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerB = $this->nasabahUserB->customer()->create([
            'name' => 'Siti Aminah', 'address' => 'Jl. Kaliurang, Sleman',
        ]);

        $this->pet = WasteType::create(['name' => 'Plastik PET', 'unit' => 'kg', 'price_per_unit' => 4000.00]);
        $this->kardus = WasteType::create(['name' => 'Kardus', 'unit' => 'kg', 'price_per_unit' => 2000.00]);
    }

    public function test_impact_dashboard_matches_manual_calculations_without_unsupported_emission_claims(): void
    {
        // Tambah 1 nasabah nonaktif untuk memastikan tidak ikut terhitung nasabah aktif
        $inactive = Customer::factory()->create(['is_active' => false]);
        $this->assertFalse($inactive->is_active);

        $depositSvc = app(DepositService::class);

        // Setoran 1 (bulan ini): 6.000 kg PET x 4000 = 24.000
        $d1 = $depositSvc->recordDeposit($this->customerA->id, $this->pet->id, 6.000, $this->petugas);

        // Setoran 2 (2 bulan lalu): 4.000 kg Kardus x 2000 = 8.000
        $d2 = $depositSvc->recordDeposit($this->customerB->id, $this->kardus->id, 4.000, $this->petugas);
        $twoMonthsAgo = now()->startOfMonth()->subMonths(2)->addDays(3);
        Deposit::whereKey($d2->id)->update(['created_at' => $twoMonthsAgo, 'updated_at' => $twoMonthsAgo]);

        // Setoran 3 (14 bulan lalu — masuk total seluruh waktu, tetapi di luar jendela grafik 12 bulan terakhir):
        // 10.000 kg Kardus x 2000 = 20.000
        $d3 = $depositSvc->recordDeposit($this->customerA->id, $this->kardus->id, 10.000, $this->petugas);
        $fourteenMonthsAgo = now()->startOfMonth()->subMonths(14)->addDays(2);
        Deposit::whereKey($d3->id)->update(['created_at' => $fourteenMonthsAgo, 'updated_at' => $fourteenMonthsAgo]);

        // Hitungan manual:
        // Total berat seluruh waktu = 6 + 4 + 10 = 20.000 kg
        // Total nilai seluruh waktu = 24.000 + 8.000 + 20.000 = Rp 52.000,00
        // Nasabah aktif = 2 (Budi & Siti)
        // Komposisi: Kardus = 14 kg (70,00%), Plastik PET = 6 kg (30,00%)
        $response = $this->actingAs($this->admin)->get(route('admin.dampak.index'));

        $response->assertOk()
            ->assertSee('Dampak Pengelolaan Sampah')
            ->assertSee('cdn.jsdelivr.net/npm/chart.js', false)
            ->assertDontSee('CO2')
            ->assertDontSee('emisi');

        $impact = $response->viewData('impact');

        $this->assertEquals(20.000, $impact['total_weight_kg']);
        $this->assertEquals(52000.00, $impact['total_deposit_value']);
        $this->assertSame(3, $impact['total_deposits_count']);
        $this->assertSame(2, $impact['active_customers_count']);

        // Seri 12 bulan terakhir hanya memuat d1 (6 kg) dan d2 (4 kg) -> jumlah 10 kg
        $this->assertCount(12, $impact['monthly_series']);
        $sum12Months = array_sum(array_column($impact['monthly_series'], 'weight_kg'));
        $this->assertEqualsWithDelta(10.000, $sum12Months, 1e-6);

        $byType = $impact['by_waste_type']->keyBy('name');
        $this->assertEquals(14.000, $byType['Kardus']->total_weight_kg);
        $this->assertEquals(70.00, $byType['Kardus']->percentage);
        $this->assertEquals(6.000, $byType['Plastik PET']->total_weight_kg);
        $this->assertEquals(30.00, $byType['Plastik PET']->percentage);
    }

    public function test_impact_dashboard_matches_seeded_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@banksampah.test')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('admin.dampak.index'));
        $response->assertOk();

        $impact = $response->viewData('impact');
        // Seeder JMP-12 menghasilkan 2.500 kg Plastik PET + 3.000 kg Kardus = 5.500 kg,
        // serta total nasabah aktif = 2 (dari setUp) + 11 (dari DatabaseSeeder) = 13 nasabah aktif.
        $this->assertEquals(5.500, $impact['total_weight_kg']);
        $this->assertSame(13, $impact['active_customers_count']);
        $this->assertEquals((float) Deposit::sum('total_value'), $impact['total_deposit_value']);
    }

    public function test_customer_sees_only_own_contribution_on_dashboard(): void
    {
        $depositSvc = app(DepositService::class);

        // Nasabah A: 2 setoran = 2.500 + 3.750 = 6.250 kg
        $depositSvc->recordDeposit($this->customerA->id, $this->pet->id, 2.500, $this->petugas);
        $depositSvc->recordDeposit($this->customerA->id, $this->kardus->id, 3.750, $this->petugas);

        // Nasabah B: 3 setoran = 5.000 + 7.000 + 7.500 = 19.500 kg
        $depositSvc->recordDeposit($this->customerB->id, $this->pet->id, 5.000, $this->petugas);
        $depositSvc->recordDeposit($this->customerB->id, $this->kardus->id, 7.000, $this->petugas);
        $depositSvc->recordDeposit($this->customerB->id, $this->kardus->id, 7.500, $this->petugas);

        $respA = $this->actingAs($this->nasabahUserA)->get(route('nasabah.dashboard'));
        $respA->assertOk()
            ->assertSee('Kontribusi saya')
            ->assertSee('6,250 kg')
            ->assertSee('2 setoran')
            ->assertDontSee('19,500 kg');

        $respB = $this->actingAs($this->nasabahUserB)->get(route('nasabah.dashboard'));
        $respB->assertOk()
            ->assertSee('Kontribusi saya')
            ->assertSee('19,500 kg')
            ->assertSee('3 setoran')
            ->assertDontSee('6,250 kg');
    }

    public function test_role_access_control_on_impact_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dampak.index'))->assertOk();
        $this->actingAs($this->petugas)->get(route('admin.dampak.index'))->assertForbidden();
        $this->actingAs($this->nasabahUserA)->get(route('admin.dampak.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dampak.index'))->assertRedirect(route('login'));
    }
}
