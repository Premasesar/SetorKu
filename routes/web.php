<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — SetorKu
|--------------------------------------------------------------------------
|
| Route yang dilindungi middleware 'role' memastikan proteksi backend
| sesuai FR-001 dan NFR-004 (Access Control).
|
| PBI-01: Authentication & Role
|
*/

// ─── Guest Routes ───────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ─── Authenticated Routes ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard redirect berdasarkan role (untuk URL /dashboard)
    Route::get('/dashboard', function () {
        return match (auth()->user()->role) {
            'admin'   => redirect()->route('admin.dashboard'),
            'petugas' => redirect()->route('petugas.dashboard'),
            'nasabah' => redirect()->route('nasabah.dashboard'),
        };
    })->name('dashboard');

    // ─── Admin Routes ───────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        // PBI-02: Pengelolaan Data Nasabah (tanpa hard delete)
        Route::resource('nasabah', CustomerController::class)
            ->parameters(['nasabah' => 'customer'])
            ->except(['destroy']);
        Route::patch('nasabah/{customer}/nonaktifkan', [CustomerController::class, 'deactivate'])
            ->name('nasabah.deactivate');
    });

    // ─── Petugas Routes ─────────────────────────────────────────
    Route::middleware('role:petugas')->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'petugas'])->name('dashboard');
        // PBI-02, PBI-04 routes akan ditambahkan di sini
    });

    // ─── Nasabah Routes ─────────────────────────────────────────
    Route::middleware('role:nasabah')->prefix('nasabah')->name('nasabah.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'nasabah'])->name('dashboard');
        // Portal nasabah routes akan ditambahkan di Sprint 2
    });
});
