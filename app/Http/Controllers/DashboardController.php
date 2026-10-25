<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;

/**
 * Controller dashboard per peran — PBI-01 & PBI-10.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService)
    {
    }

    /**
     * Dashboard Admin — ringkasan operasional & penjemputan terjadwal hari ini.
     */
    public function admin()
    {
        $stats = $this->reportingService->adminDashboard();

        return view('dashboard.admin', compact('stats'));
    }

    /**
     * Dashboard Petugas — tugas penjemputan hari ini & transaksi yang dicatat hari ini.
     */
    public function petugas(Request $request)
    {
        $stats = $this->reportingService->officerDashboard($request->user());

        return view('dashboard.petugas', compact('stats'));
    }

    /**
     * Dashboard Nasabah — saldo, 5 transaksi terakhir, & status penjemputan aktif terakhir.
     */
    public function nasabah(Request $request, CustomerPortalController $portalController)
    {
        return $portalController->dashboard($request);
    }
}
