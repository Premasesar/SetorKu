<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;

/**
 * Laporan Rekap Bulanan SetorKu (khusus Admin) — PBI-10.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2100'],
        ]);

        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);

        $report = $this->reportingService->monthlyReport($month, $year);

        return view('reports.index', compact('report', 'month', 'year'));
    }
}
