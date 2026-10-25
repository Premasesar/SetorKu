<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WasteType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Layanan transaksi setoran sampah — PBI-04 / FR-004 / BR-003.
 *
 * Seluruh kalkulasi harga & penambahan saldo dilakukan atomik di backend
 * dengan DB::transaction + lockForUpdate pada baris nasabah.
 */
class DepositService
{
    public function recordDeposit(int $customerId, int $wasteTypeId, float|string $weight, User $officer): Deposit
    {
        abort_unless($officer->isPetugas(), 403);

        $weightNum = round((float) $weight, 3);
        if ($weightNum <= 0) {
            throw ValidationException::withMessages([
                'weight' => 'Berat setoran harus lebih besar dari 0.',
            ]);
        }

        return DB::transaction(function () use ($customerId, $wasteTypeId, $weightNum, $officer) {
            $customer = Customer::whereKey($customerId)->lockForUpdate()->first();

            if ($customer === null || ! $customer->is_active) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Nasabah tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            $wasteType = WasteType::whereKey($wasteTypeId)->lockForUpdate()->first();

            if ($wasteType === null || ! $wasteType->is_active) {
                throw ValidationException::withMessages([
                    'waste_type_id' => 'Jenis sampah tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            $pricePerUnit = round((float) $wasteType->price_per_unit, 2);
            $totalValue = round($weightNum * $pricePerUnit, 2);

            $deposit = Deposit::create([
                'customer_id' => $customer->id,
                'waste_type_id' => $wasteType->id,
                'officer_id' => $officer->id,
                'weight' => $weightNum,
                'price_per_unit' => $pricePerUnit,
                'total_value' => $totalValue,
            ]);

            $customer->balance = round((float) $customer->balance + $totalValue, 2);
            $customer->save();

            return $deposit;
        });
    }
}
