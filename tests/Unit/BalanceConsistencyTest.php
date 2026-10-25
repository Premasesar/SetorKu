<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\User;
use App\Models\WasteType;
use App\Services\DepositService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit Test Konsistensi Saldo (PBI-05):
 * customers.balance === sum(deposits.total_value) - sum(withdrawals.amount),
 * dan saldo tidak pernah bernilai negatif.
 */
class BalanceConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_balance_always_equals_total_deposits_minus_total_withdrawals(): void
    {
        $officer = User::create([
            'name' => 'Petugas Unit',
            'email' => 'officer@unit.test',
            'password' => 'password',
            'role' => 'petugas',
        ]);

        $customer = Customer::factory()->create(['balance' => 0]);

        $pet = WasteType::create(['name' => 'Plastik PET', 'unit' => 'kg', 'price_per_unit' => 3500.00]);
        $kardus = WasteType::create(['name' => 'Kardus', 'unit' => 'kg', 'price_per_unit' => 2100.00]);

        $depositService = app(DepositService::class);
        $withdrawalService = app(WithdrawalService::class);

        $assertConsistent = function () use ($customer): void {
            $fresh = $customer->fresh();
            $expected = $fresh->calculatedBalance();
            $actual = round((float) $fresh->balance, 2);

            $this->assertGreaterThanOrEqual(0.00, $actual, 'Saldo tidak boleh negatif.');
            $this->assertEquals($expected, $actual, 'Saldo harus sama dengan total setoran dikurangi total penarikan.');
        };

        // Awal: 0
        $assertConsistent();

        // Setoran 1: 2.350 kg x 3500 = 8225.00
        $depositService->recordDeposit($customer->id, $pet->id, 2.350, $officer);
        $assertConsistent();

        // Setoran 2: 4.125 kg x 2100 = 8662.50 -> total 16887.50
        $depositService->recordDeposit($customer->id, $kardus->id, 4.125, $officer);
        $assertConsistent();

        // Ubah harga master Plastik PET -> tidak boleh mengubah konsistensi saldo lama
        $pet->update(['price_per_unit' => 4200.00]);
        $assertConsistent();

        // Setoran 3 memakai harga baru: 1.500 kg x 4200 = 6300.00 -> total 23187.50
        $depositService->recordDeposit($customer->id, $pet->id, 1.500, $officer);
        $assertConsistent();

        // Penarikan 1: 10000.00 -> sisa 13187.50
        $withdrawalService->recordWithdrawal($customer->id, 10000.00, $officer);
        $assertConsistent();

        // Penarikan melebihi saldo harus gagal & tetap konsisten
        try {
            $withdrawalService->recordWithdrawal($customer->id, 20000.00, $officer);
            $this->fail('Penarikan melebihi saldo seharusnya melempar ValidationException.');
        } catch (ValidationException) {
            // diharapkan
        }
        $assertConsistent();

        // Penarikan 2: 3187.50 -> sisa 10000.00
        $withdrawalService->recordWithdrawal($customer->id, 3187.50, $officer);
        $assertConsistent();

        // Penarikan penuh sisa saldo: 10000.00 -> sisa 0.00
        $withdrawalService->recordWithdrawal($customer->id, 10000.00, $officer);
        $assertConsistent();
        $this->assertEquals(0.00, (float) $customer->fresh()->balance);
    }
}
