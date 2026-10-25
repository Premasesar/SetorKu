<?php

namespace App\Services;

use App\Enums\PickupStatus;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Layanan Statistik Dashboard & Laporan Rekap Bulanan — PBI-10.
 */
class ReportingService
{
    public function adminDashboard(?Carbon $referenceTime = null): array
    {
        $now = $referenceTime ? $referenceTime->copy() : now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $today = $now->toDateString();

        $monthDepositsQuery = Deposit::whereBetween('created_at', [$monthStart, $monthEnd]);
        $monthWithdrawalsQuery = Withdrawal::whereBetween('created_at', [$monthStart, $monthEnd]);

        $rawStatusCounts = PickupRequest::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pickupsByStatus = [];
        foreach (PickupStatus::cases() as $case) {
            $pickupsByStatus[$case->value] = (int) ($rawStatusCounts[$case->value] ?? 0);
        }

        $todayScheduledPickups = PickupRequest::with(['customer', 'officer'])
            ->where('status', PickupRequest::STATUS_TERJADWAL)
            ->whereDate('scheduled_date', $today)
            ->orderBy('id')
            ->get();

        return [
            'active_customers_count' => Customer::active()->count(),
            'total_customer_balance' => round((float) Customer::sum('balance'), 2),
            'month_deposits' => [
                'count' => (clone $monthDepositsQuery)->count(),
                'total_weight' => round((float) (clone $monthDepositsQuery)->sum('weight'), 3),
                'total_value' => round((float) (clone $monthDepositsQuery)->sum('total_value'), 2),
            ],
            'month_withdrawals' => [
                'count' => (clone $monthWithdrawalsQuery)->count(),
                'total_amount' => round((float) (clone $monthWithdrawalsQuery)->sum('amount'), 2),
            ],
            'pickups_by_status' => $pickupsByStatus,
            'today_scheduled_pickups' => $todayScheduledPickups,
        ];
    }

    public function officerDashboard(User $officer, ?Carbon $referenceTime = null): array
    {
        $now = $referenceTime ? $referenceTime->copy() : now();
        $dayStart = $now->copy()->startOfDay();
        $dayEnd = $now->copy()->endOfDay();
        $today = $now->toDateString();

        $todayPickups = PickupRequest::with('customer')
            ->where('officer_id', $officer->id)
            ->where('status', PickupRequest::STATUS_TERJADWAL)
            ->whereDate('scheduled_date', $today)
            ->orderByRaw('CASE WHEN route_sequence IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('route_sequence')
            ->orderBy('id')
            ->get();

        $todayDepositsQuery = Deposit::where('officer_id', $officer->id)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        $todayWithdrawalsQuery = Withdrawal::where('officer_id', $officer->id)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        return [
            'today_pickups' => $todayPickups,
            'today_deposits_count' => (clone $todayDepositsQuery)->count(),
            'today_deposits_value' => round((float) (clone $todayDepositsQuery)->sum('total_value'), 2),
            'today_withdrawals_count' => (clone $todayWithdrawalsQuery)->count(),
            'today_withdrawals_amount' => round((float) (clone $todayWithdrawalsQuery)->sum('amount'), 2),
        ];
    }

    public function monthlyReport(int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // (1) Rekap setoran per jenis sampah
        $depositAgg = Deposit::query()
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'waste_type_id',
                DB::raw('COUNT(*) as transactions_count'),
                DB::raw('COALESCE(SUM(weight), 0) as total_weight'),
                DB::raw('COALESCE(SUM(total_value), 0) as total_value'),
            )
            ->groupBy('waste_type_id')
            ->get()
            ->keyBy('waste_type_id');

        $wasteTypes = WasteType::orderBy('name')->get();
        $depositsByWasteType = $wasteTypes->map(function (WasteType $wt) use ($depositAgg) {
            $agg = $depositAgg->get($wt->id);

            return (object) [
                'waste_type_id' => $wt->id,
                'name' => $wt->name,
                'unit' => $wt->unit,
                'transactions_count' => (int) ($agg->transactions_count ?? 0),
                'total_weight' => round((float) ($agg->total_weight ?? 0), 3),
                'total_value' => round((float) ($agg->total_value ?? 0), 2),
            ];
        });

        $depositTotals = [
            'transactions_count' => (int) $depositsByWasteType->sum('transactions_count'),
            'total_weight' => round((float) $depositsByWasteType->sum('total_weight'), 3),
            'total_value' => round((float) $depositsByWasteType->sum('total_value'), 2),
        ];

        // (2) Rekap penarikan
        $withdrawalsQuery = Withdrawal::whereBetween('created_at', [$start, $end]);
        $withdrawalSummary = [
            'transactions_count' => (clone $withdrawalsQuery)->count(),
            'total_amount' => round((float) (clone $withdrawalsQuery)->sum('amount'), 2),
        ];

        // (3) Rekap penjemputan per status (berdasarkan waktu pembuatan pengajuan pada bulan/tahun terkait)
        $rawPickups = PickupRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pickupsByStatus = [];
        $pickupTotal = 0;
        foreach (PickupStatus::cases() as $case) {
            $count = (int) ($rawPickups[$case->value] ?? 0);
            $pickupsByStatus[] = (object) [
                'status' => $case->value,
                'label' => $case->label(),
                'badge_class' => $case->badgeClass(),
                'count' => $count,
            ];
            $pickupTotal += $count;
        }

        return [
            'month' => $month,
            'year' => $year,
            'period_label' => $start->translatedFormat('F Y'),
            'deposits_by_waste_type' => $depositsByWasteType,
            'deposit_totals' => $depositTotals,
            'withdrawal_summary' => $withdrawalSummary,
            'pickups_by_status' => $pickupsByStatus,
            'pickup_total' => $pickupTotal,
        ];
    }

    /**
     * Statistik Halaman Admin "Dampak" (seluruh waktu + tren 12 bulan terakhir + distribusi per jenis) — PBI-13.
     * Hanya bersumber dari data nyata yang tersimpan pada tabel deposits & customers (tanpa estimasi emisi/CO2).
     *
     * @return array<string, mixed>
     */
    public function impactDashboard(?Carbon $referenceTime = null): array
    {
        $now = $referenceTime ? $referenceTime->copy() : now();

        $totalWeightKg = round((float) Deposit::sum('weight'), 3);
        $totalDepositValue = round((float) Deposit::sum('total_value'), 2);
        $totalDepositsCount = (int) Deposit::count();
        $activeCustomersCount = (int) Customer::active()->count();

        // Tren berat (kg) per bulan selama 12 bulan terakhir (kompatibel penuh MySQL & SQLite)
        $firstMonthStart = $now->copy()->startOfMonth()->subMonths(11);
        $lastMonthEnd = $now->copy()->endOfMonth();

        $recentDeposits = Deposit::query()
            ->whereBetween('created_at', [$firstMonthStart, $lastMonthEnd])
            ->get(['weight', 'created_at']);

        $weightsByMonthKey = [];
        foreach ($recentDeposits as $deposit) {
            $key = $deposit->created_at->format('Y-m');
            $weightsByMonthKey[$key] = ($weightsByMonthKey[$key] ?? 0.0) + (float) $deposit->weight;
        }

        $monthlySeries = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthCursor = $now->copy()->startOfMonth()->subMonths($i);
            $key = $monthCursor->format('Y-m');
            $monthlySeries[] = [
                'key' => $key,
                'label' => $monthCursor->translatedFormat('M Y'),
                'weight_kg' => round((float) ($weightsByMonthKey[$key] ?? 0.0), 3),
            ];
        }

        // Tabel per jenis sampah (total kg & persentase terhadap seluruh berat terkelola)
        $aggByType = Deposit::query()
            ->select(
                'waste_type_id',
                DB::raw('COUNT(*) as deposits_count'),
                DB::raw('COALESCE(SUM(weight), 0) as total_weight'),
            )
            ->groupBy('waste_type_id')
            ->get()
            ->keyBy('waste_type_id');

        $byWasteType = WasteType::orderBy('name')->get()->map(function (WasteType $wt) use ($aggByType, $totalWeightKg) {
            $agg = $aggByType->get($wt->id);
            $weight = round((float) ($agg->total_weight ?? 0.0), 3);
            $percentage = $totalWeightKg > 0.0
                ? round(($weight / $totalWeightKg) * 100.0, 2)
                : 0.0;

            return (object) [
                'waste_type_id' => $wt->id,
                'name' => $wt->name,
                'unit' => $wt->unit,
                'deposits_count' => (int) ($agg->deposits_count ?? 0),
                'total_weight_kg' => $weight,
                'percentage' => $percentage,
            ];
        })->sortByDesc('total_weight_kg')->values();

        return [
            'total_weight_kg' => $totalWeightKg,
            'total_deposit_value' => $totalDepositValue,
            'total_deposits_count' => $totalDepositsCount,
            'active_customers_count' => $activeCustomersCount,
            'monthly_series' => $monthlySeries,
            'by_waste_type' => $byWasteType,
        ];
    }
}
