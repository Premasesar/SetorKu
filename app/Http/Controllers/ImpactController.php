<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;

/**
 * Halaman Admin "Dampak" (Rekapitulasi Sampah Terkelola Seluruh Waktu) — PBI-13.
 */
class ImpactController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $impact = $this->reportingService->impactDashboard();

        return view('impact.index', compact('impact'));
    }
}
