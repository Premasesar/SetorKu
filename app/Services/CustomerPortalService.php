<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Layanan Portal Nasabah — PBI-06 / FR-006.
 *
 * Menyediakan kueri gabungan setoran & penarikan yang terisolasi ketat
 * untuk satu nasabah (customer_id), terurut waktu terbaru, dan terpaginasi.
 */
class CustomerPortalService
{
    public function recentTransactions(Customer $customer, int $limit = 5): Collection
    {
        return $this->combinedTransactionsQuery($customer)
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => $this->transformRow($row));
    }

    public function paginatedTransactions(Customer $customer, int $perPage = 10, string $type = 'all'): LengthAwarePaginator
    {
        return $this->combinedTransactionsQuery($customer, $type)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (object $row) => $this->transformRow($row));
    }

    private function combinedTransactionsQuery(Customer $customer, string $type = 'all'): Builder
    {
        $deposits = DB::table('deposits')
            ->join('waste_types', 'deposits.waste_type_id', '=', 'waste_types.id')
            ->join('users as officers', 'deposits.officer_id', '=', 'officers.id')
            ->where('deposits.customer_id', $customer->id)
            ->selectRaw(
                "'setoran' as type, " .
                "deposits.id as record_id, " .
                "deposits.total_value as amount, " .
                "deposits.weight as weight, " .
                "waste_types.name as waste_type_name, " .
                "waste_types.unit as waste_unit, " .
                "deposits.price_per_unit as price_per_unit, " .
                "officers.name as officer_name, " .
                "deposits.created_at as transacted_at"
            );

        $withdrawals = DB::table('withdrawals')
            ->join('users as officers', 'withdrawals.officer_id', '=', 'officers.id')
            ->where('withdrawals.customer_id', $customer->id)
            ->selectRaw(
                "'penarikan' as type, " .
                "withdrawals.id as record_id, " .
                "withdrawals.amount as amount, " .
                "NULL as weight, " .
                "NULL as waste_type_name, " .
                "NULL as waste_unit, " .
                "NULL as price_per_unit, " .
                "officers.name as officer_name, " .
                "withdrawals.created_at as transacted_at"
            );

        $source = match ($type) {
            'setoran' => $deposits,
            'penarikan' => $withdrawals,
            default => $deposits->unionAll($withdrawals),
        };

        return DB::query()
            ->fromSub($source, 'combined_transactions')
            ->orderByDesc('transacted_at')
            ->orderByDesc('record_id');
    }

    private function transformRow(object $row): object
    {
        $id = (int) $row->record_id;
        $isDeposit = $row->type === 'setoran';

        return (object) [
            'type' => $row->type,
            'record_id' => $id,
            'code' => sprintf('%s-%04d', $isDeposit ? 'STR' : 'TRK', $id),
            'amount' => (float) $row->amount,
            'weight' => $row->weight !== null ? (float) $row->weight : null,
            'waste_type_name' => $row->waste_type_name,
            'waste_unit' => $row->waste_unit,
            'price_per_unit' => $row->price_per_unit !== null ? (float) $row->price_per_unit : null,
            'officer_name' => $row->officer_name,
            'transacted_at' => $row->transacted_at ? Carbon::parse($row->transacted_at) : null,
        ];
    }
}
