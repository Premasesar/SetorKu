<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Controller dashboard placeholder untuk setiap role.
 *
 * Mendukung:
 * - FR-001: Redirect sesuai role setelah login
 * - UC-01: Melakukan Login (hasil akhir redirect)
 */
class DashboardController extends Controller
{
    /**
     * Dashboard Admin — halaman utama admin setelah login.
     */
    public function admin()
    {
        return view('dashboard.admin');
    }

    /**
     * Dashboard Petugas — halaman utama petugas setelah login.
     */
    public function petugas()
    {
        return view('dashboard.petugas');
    }

    /**
     * Dashboard Nasabah — portal utama nasabah setelah login.
     */
    public function nasabah()
    {
        return view('dashboard.nasabah');
    }
}
