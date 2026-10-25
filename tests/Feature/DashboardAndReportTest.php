<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Services\DepositService;
use App\Services\PickupService;
use App\Services\WithdrawalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature Test PBI-10: Dashboard per Peran, Laporan Rekap Bulanan, & Seeder Penjemputan.
 */
class DashboardAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboards_and_monthly_report_match_manual_calculations(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-25 10:00:00'));

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin']);
        $officer1 = User::create(['name' => 'Petugas 1', 'email' => 'p1@test.com', 'password' => 'password', 'role' => 'petugas']);
        $officer2 = User::create(['name' => 'Petugas 2', 'email' => 'p2@test.com', 'password' => 'password', 'role' => 'petugas']);

        $nasabahUser1 = User::create(['name' => 'Nasabah Aktif 1', 'email' => 'n1@test.com', 'password' => 'password', 'role' => 'nasabah']);
        $customer1 = $nasabahUser1->customer()->create([
            'name' => 'Nasabah Aktif 1', 'address' => 'Jl. Malioboro 1', 'latitude' => -7.7925, 'longitude' => 110.3658,
        ]);

        $nasabahUser2 = User::create(['name' => 'Nasabah Aktif 2', 'email' => 'n2@test.com', 'password' => 'password', 'role' => 'nasabah']);
        $customer2 = $nasabahUser2->customer()->create([
            'name' => 'Nasabah Aktif 2', 'address' => 'Jl. Kaliurang 2', 'latitude' => -7.7630, 'longitude' => 110.3789,
        ]);

        $inactiveCustomer = Customer::factory()->inactive()->create(['balance' => 5000.00]);

        $pet = WasteType::create(['name' => 'Plastik PET', 'unit' => 'kg', 'price_per_unit' => 4000.00]);
        $kardus = WasteType::create(['name' => 'Kardus', 'unit' => 'kg', 'price_per_unit' => 2000.00]);

        $depositService = app(DepositService::class);
        $withdrawalService = app(WithdrawalService::class);
        $pickupService = app(PickupService::class);

        // Transaksi bulan lalu (September 2026): 3 kg PET x 4000 = 12.000
        Carbon::setTestNow(Carbon::parse('2026-09-15 09:00:00'));
        $depositService->recordDeposit($customer1->id, $pet->id, 3.000, $officer1);

        // Transaksi bulan ini (Oktober 2026), kemarin (24 Okt): 5 kg Kardus x 2000 = 10.000 oleh officer2
        Carbon::setTestNow(Carbon::parse('2026-10-24 14:00:00'));
        $depositService->recordDeposit($customer2->id, $kardus->id, 5.000, $officer2);

        // Transaksi hari ini (25 Okt 2026) oleh officer1:
        // - Setoran: 2.5 kg PET x 4000 = 10.000, dan 1.5 kg Kardus x 2000 = 3.000
        // - Penarikan: Rp 5.000 dari customer1
        Carbon::setTestNow(Carbon::parse('2026-10-25 10:00:00'));
        $depositService->recordDeposit($customer1->id, $pet->id, 2.500, $officer1);
        $depositService->recordDeposit($customer1->id, $kardus->id, 1.500, $officer1);
        $withdrawalService->recordWithdrawal($customer1->id, 5000.00, $officer1);

        // Penjemputan pada Oktober 2026: 1 menunggu, 1 ditolak, 2 terjadwal (1 hari ini u/ officer1, 1 besok u/ officer2)
        $pWait = $pickupService->submitRequest($customer1, [
            'requested_date' => '2026-10-26', 'address' => 'Alamat 1', 'latitude' => -7.7925, 'longitude' => 110.3658,
        ]);
        $pRej = $pickupService->submitRequest($customer2, [
            'requested_date' => '2026-10-25', 'address' => 'Alamat 2', 'latitude' => -7.7630, 'longitude' => 110.3789,
        ]);
        $pickupService->reject($pRej, 'Penuh', $admin);

        $pToday = $pickupService->submitRequest($customer1, [
            'requested_date' => '2026-10-25', 'address' => 'Alamat Jemput Hari Ini', 'latitude' => -7.7925, 'longitude' => 110.3658,
        ]);
        $pickupService->schedule($pToday, '2026-10-25', $officer1->id, $admin);

        $pTomorrow = $pickupService->submitRequest($customer2, [
            'requested_date' => '2026-10-26', 'address' => 'Alamat Besok', 'latitude' => -7.7630, 'longitude' => 110.3789,
        ]);
        $pickupService->schedule($pTomorrow, '2026-10-26', $officer2->id, $admin);

        // --- Verifikasi Dashboard Admin ---
        // Nasabah aktif = 2 (inactiveCustomer tidak dihitung aktif)
        // Total saldo seluruh nasabah = customer1 (12000 + 10000 + 3000 - 5000 = 20000) + customer2 (10000) + inactive (5000) = 35000
        // Setoran Oktober 2026 = 3 trx, berat 5 + 2.5 + 1.5 = 9.000 kg, nilai 10000 + 10000 + 3000 = 23000
        // Penarikan Oktober 2026 = 1 trx, nominal 5000
        $adminDash = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminDash->assertOk()->assertSee('Dashboard Admin');
        $statsAdmin = $adminDash->viewData('stats');

        $this->assertSame(2, $statsAdmin['active_customers_count']);
        $this->assertEquals(35000.00, $statsAdmin['total_customer_balance']);
        $this->assertSame(3, $statsAdmin['month_deposits']['count']);
        $this->assertEquals(9.000, $statsAdmin['month_deposits']['total_weight']);
        $this->assertEquals(23000.00, $statsAdmin['month_deposits']['total_value']);
        $this->assertSame(1, $statsAdmin['month_withdrawals']['count']);
        $this->assertEquals(5000.00, $statsAdmin['month_withdrawals']['total_amount']);
        $this->assertSame(['menunggu' => 1, 'ditolak' => 1, 'terjadwal' => 2, 'selesai' => 0], $statsAdmin['pickups_by_status']);
        $this->assertCount(1, $statsAdmin['today_scheduled_pickups']);
        $this->assertSame($pToday->id, $statsAdmin['today_scheduled_pickups']->first()->id);

        // --- Verifikasi Dashboard Petugas 1 ---
        // Tugas hari ini = 1 ($pToday), setoran dicatat hari ini oleh officer1 = 2, penarikan hari ini oleh officer1 = 1
        $officerDash = $this->actingAs($officer1)->get(route('petugas.dashboard'));
        $officerDash->assertOk()->assertSee('Dashboard Petugas');
        $statsOfficer = $officerDash->viewData('stats');

        $this->assertCount(1, $statsOfficer['today_pickups']);
        $this->assertSame(2, $statsOfficer['today_deposits_count']);
        $this->assertEquals(13000.00, $statsOfficer['today_deposits_value']);
        $this->assertSame(1, $statsOfficer['today_withdrawals_count']);
        $this->assertEquals(5000.00, $statsOfficer['today_withdrawals_amount']);

        // --- Verifikasi Dashboard Nasabah 1 (menampilkan penjemputan aktif terakhir = $pToday) ---
        $nasabahDash = $this->actingAs($nasabahUser1)->get(route('nasabah.dashboard'));
        $nasabahDash->assertOk()
            ->assertSee('Portal Nasabah')
            ->assertSee($pToday->code)
            ->assertSee('Terjadwal');
        $this->assertSame($pToday->id, $nasabahDash->viewData('activePickup')->id);

        // --- Verifikasi Laporan Rekap Bulanan Oktober 2026 ---
        $reportOct = $this->actingAs($admin)->get(route('admin.laporan.index', ['month' => 10, 'year' => 2026]));
        $reportOct->assertOk();
        $rep = $reportOct->viewData('report');

        $byType = $rep['deposits_by_waste_type']->keyBy('name');
        // Kardus Okt: 2 trx (5.0 + 1.5 = 6.5 kg), nilai 13.000
        $this->assertSame(2, $byType['Kardus']->transactions_count);
        $this->assertEquals(6.500, $byType['Kardus']->total_weight);
        $this->assertEquals(13000.00, $byType['Kardus']->total_value);
        // Plastik PET Okt: 1 trx (2.5 kg), nilai 10.000 (transaksi Sept 3 kg tidak ikut!)
        $this->assertSame(1, $byType['Plastik PET']->transactions_count);
        $this->assertEquals(2.500, $byType['Plastik PET']->total_weight);
        $this->assertEquals(10000.00, $byType['Plastik PET']->total_value);
        // Baris Total Okt: 3 trx, 9.0 kg, Rp 23.000
        $this->assertSame(3, $rep['deposit_totals']['transactions_count']);
        $this->assertEquals(9.000, $rep['deposit_totals']['total_weight']);
        $this->assertEquals(23000.00, $rep['deposit_totals']['total_value']);
        // Penarikan Okt: 1 trx, Rp 5.000
        $this->assertSame(1, $rep['withdrawal_summary']['transactions_count']);
        $this->assertEquals(5000.00, $rep['withdrawal_summary']['total_amount']);
        // Penjemputan Okt: total 4
        $this->assertSame(4, $rep['pickup_total']);

        // --- Verifikasi Laporan September 2026 ---
        $reportSep = $this->actingAs($admin)->get(route('admin.laporan.index', ['month' => 9, 'year' => 2026]));
        $repSep = $reportSep->viewData('report');
        $this->assertSame(1, $repSep['deposit_totals']['transactions_count']);
        $this->assertEquals(3.000, $repSep['deposit_totals']['total_weight']);
        $this->assertEquals(12000.00, $repSep['deposit_totals']['total_value']);
        $this->assertSame(0, $repSep['withdrawal_summary']['transactions_count']);

        Carbon::setTestNow();
    }

    public function test_database_seeder_is_idempotent_and_creates_twelve_yogyakarta_pickups_with_eight_scheduled_today(): void
    {
        $this->seed(DatabaseSeeder::class);
        $balanceAfterFirstSeed = round((float) Customer::sum('balance'), 2);

        // Jalankan kali kedua untuk menguji idempotensi
        $this->seed(DatabaseSeeder::class);
        $balanceAfterSecondSeed = round((float) Customer::sum('balance'), 2);

        $this->assertSame(12, PickupRequest::count());
        $this->assertEquals($balanceAfterFirstSeed, $balanceAfterSecondSeed);

        $scheduledToday = PickupRequest::where('status', PickupRequest::STATUS_TERJADWAL)
            ->whereDate('scheduled_date', now()->toDateString())
            ->get();

        $this->assertGreaterThanOrEqual(8, $scheduledToday->count());
        $this->assertSame(2, $scheduledToday->pluck('officer_id')->unique()->count());

        // Pastikan variasi status ada dan angka laporan sesuai data seeder
        $admin = User::where('role', 'admin')->firstOrFail();
        $reportResp = $this->actingAs($admin)->get(route('admin.laporan.index'));
        $reportResp->assertOk();
        $rep = $reportResp->viewData('report');

        $this->assertSame(12, $rep['pickup_total']);
        $this->assertEquals($balanceAfterSecondSeed, $rep['deposit_totals']['total_value']);
    }

    public function test_report_route_is_admin_only(): void
    {
        $petugas = User::create(['name' => 'Petugas', 'email' => 'p@test.com', 'password' => 'password', 'role' => 'petugas']);
        $nasabah = User::create(['name' => 'Nasabah', 'email' => 'n@test.com', 'password' => 'password', 'role' => 'nasabah']);

        $this->actingAs($petugas)->get(route('admin.laporan.index'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('admin.laporan.index'))->assertForbidden();
    }

    public function test_guest_cannot_access_report(): void
    {
        $this->get(route('admin.laporan.index'))->assertRedirect(route('login'));
    }
}
