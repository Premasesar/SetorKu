<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Layanan transaksi penarikan saldo nasabah — PBI-05 / FR-005 / BR-003.
 *
 * Seluruh validasi kecukupan saldo & pengurangan saldo dilakukan secara atomik
 * di backend menggunakan DB::transaction + lockForUpdate pada baris nasabah.
 * Saldo nasabah dijamin tidak pernah bernilai negatif.
 */
class WithdrawalService
{
    public function recordWithdrawal(int $customerId, float|string $amount, User $officer): Withdrawal
    {
        abort_unless($officer->isPetugas(), 403);

        $amountNum = round((float) $amount, 2);
        if ($amountNum <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal penarikan harus lebih besar dari 0.',
            ]);
        }

        return DB::transaction(function () use ($customerId, $amountNum, $officer) {
            $customer = Customer::whereKey($customerId)->lockForUpdate()->first();

            if ($customer === null || ! $customer->is_active) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Nasabah tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            $currentBalance = round((float) $customer->balance, 2);

            if ($amountNum > $currentBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo nasabah tidak mencukupi untuk penarikan ini.',
                ]);
            }

            $newBalance = round($currentBalance - $amountNum, 2);

            if ($newBalance < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo nasabah tidak boleh menjadi negatif.',
                ]);
            }

            $withdrawal = Withdrawal::create([
                'customer_id' => $customer->id,
                'officer_id' => $officer->id,
                'amount' => $amountNum,
            ]);

            $customer->balance = $newBalance;
            $customer->save();

            return $withdrawal;
        });
    }
}
