<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WasteTypeController;
use App\Http\Controllers\WithdrawalController;
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

        // PBI-02 & PBI-02b: Pengelolaan Data Nasabah (tanpa hard delete)
        Route::resource('nasabah', CustomerController::class)
            ->parameters(['nasabah' => 'customer'])
            ->except(['destroy']);
        Route::patch('nasabah/{customer}/nonaktifkan', [CustomerController::class, 'deactivate'])
            ->name('nasabah.deactivate');
        Route::patch('nasabah/{customer}/aktifkan', [CustomerController::class, 'activate'])
            ->name('nasabah.activate');

        // PBI-03: Pengelolaan Jenis Sampah & Harga (tanpa hard delete)
        Route::resource('jenis-sampah', WasteTypeController::class)
            ->parameters(['jenis-sampah' => 'wasteType'])
            ->except(['destroy']);
        Route::patch('jenis-sampah/{wasteType}/nonaktifkan', [WasteTypeController::class, 'deactivate'])
            ->name('jenis-sampah.deactivate');
        Route::patch('jenis-sampah/{wasteType}/aktifkan', [WasteTypeController::class, 'activate'])
            ->name('jenis-sampah.activate');

        // PBI-04: Admin hanya melihat riwayat dan bukti setoran
        Route::resource('setoran', DepositController::class)
            ->parameters(['setoran' => 'deposit'])
            ->only(['index', 'show']);

        // PBI-05: Admin hanya melihat riwayat dan bukti penarikan
        Route::resource('penarikan', WithdrawalController::class)
            ->parameters(['penarikan' => 'withdrawal'])
            ->only(['index', 'show']);
    });

    // ─── Petugas Routes ─────────────────────────────────────────
    Route::middleware('role:petugas')->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'petugas'])->name('dashboard');

        // PBI-02b: Petugas hanya boleh melihat/mencari daftar, detail, dan menambah nasabah baru
        Route::resource('nasabah', CustomerController::class)
            ->parameters(['nasabah' => 'customer'])
            ->only(['index', 'create', 'store', 'show']);

        // PBI-03: Petugas hanya boleh melihat jenis sampah aktif
        Route::get('jenis-sampah', [WasteTypeController::class, 'index'])
            ->name('jenis-sampah.index');

        // PBI-04: Petugas mencatat setoran & melihat riwayat/bukti setoran
        Route::resource('setoran', DepositController::class)
            ->parameters(['setoran' => 'deposit'])
            ->only(['index', 'create', 'store', 'show']);

        // PBI-05: Petugas mencatat penarikan & melihat riwayat/bukti penarikan
        Route::resource('penarikan', WithdrawalController::class)
            ->parameters(['penarikan' => 'withdrawal'])
            ->only(['index', 'create', 'store', 'show']);
    });

    // ─── Nasabah Routes ─────────────────────────────────────────
    Route::middleware('role:nasabah')->prefix('nasabah')->name('nasabah.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'nasabah'])->name('dashboard');
        // Portal nasabah routes akan ditambahkan di Sprint 2
    });
});
