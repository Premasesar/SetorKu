<?php

use App\Http\Controllers\AdminPickupController;
use App\Http\Controllers\AdminRouteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPickupController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ImpactController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\OfficerPickupController;
use App\Http\Controllers\ReportController;
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
        Route::get('nasabah/{customer}/kartu', [CustomerController::class, 'card'])
            ->name('nasabah.card');

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

        // PBI-06b: Kelola Petugas (khusus akun ber-role petugas, tanpa hard delete)
        Route::resource('petugas', OfficerController::class)
            ->parameters(['petugas' => 'officer'])
            ->except(['destroy']);
        Route::patch('petugas/{officer}/reset-password', [OfficerController::class, 'resetPassword'])
            ->name('petugas.reset-password');
        Route::patch('petugas/{officer}/nonaktifkan', [OfficerController::class, 'deactivate'])
            ->name('petugas.deactivate');
        Route::patch('petugas/{officer}/aktifkan', [OfficerController::class, 'activate'])
            ->name('petugas.activate');

        // PBI-08: Kelola Penjemputan oleh Admin (tolak, jadwalkan, ubah jadwal)
        Route::get('penjemputan', [AdminPickupController::class, 'index'])
            ->name('penjemputan.index');
        Route::get('penjemputan/{pickupRequest}', [AdminPickupController::class, 'show'])
            ->name('penjemputan.show');
        Route::patch('penjemputan/{pickupRequest}/tolak', [AdminPickupController::class, 'reject'])
            ->name('penjemputan.reject');
        Route::patch('penjemputan/{pickupRequest}/jadwalkan', [AdminPickupController::class, 'schedule'])
            ->name('penjemputan.schedule');
        Route::patch('penjemputan/{pickupRequest}/ubah-jadwal', [AdminPickupController::class, 'reschedule'])
            ->name('penjemputan.reschedule');

        // PBI-10: Laporan Rekap Bulanan (khusus Admin)
        Route::get('laporan', [ReportController::class, 'index'])
            ->name('laporan.index');

        // PBI-11: Optimasi Rute Penjemputan (Admin menghitung & menyimpan urutan)
        Route::get('rute-penjemputan', [AdminRouteController::class, 'index'])
            ->name('rute.index');
        Route::post('rute-penjemputan/simpan', [AdminRouteController::class, 'save'])
            ->name('rute.save');

        // PBI-13: Dashboard Dampak Pengelolaan Sampah (khusus Admin)
        Route::get('dampak', [ImpactController::class, 'index'])
            ->name('dampak.index');
    });

    // ─── Petugas Routes ─────────────────────────────────────────
    Route::middleware('role:petugas')->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'petugas'])->name('dashboard');

        // PBI-02b & PBI-12: Petugas melihat/mencari daftar, detail, cetak kartu QR, dan menambah nasabah baru
        Route::resource('nasabah', CustomerController::class)
            ->parameters(['nasabah' => 'customer'])
            ->only(['index', 'create', 'store', 'show']);
        Route::get('nasabah/{customer}/kartu', [CustomerController::class, 'card'])
            ->name('nasabah.card');

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

        // PBI-09 & PBI-11: Tugas Penjemputan Petugas, Peta Rute Read-Only & Pencatatan Hasil menjadi Setoran
        Route::get('penjemputan', [OfficerPickupController::class, 'index'])
            ->name('penjemputan.index');
        Route::get('penjemputan/{pickupRequest}', [OfficerPickupController::class, 'show'])
            ->name('penjemputan.show');
        Route::post('penjemputan/{pickupRequest}/selesaikan', [OfficerPickupController::class, 'complete'])
            ->name('penjemputan.complete');
        Route::get('rute/{officer?}', [OfficerPickupController::class, 'route'])
            ->name('rute.show');
    });

    // ─── Nasabah Routes ─────────────────────────────────────────
    Route::middleware('role:nasabah')->prefix('nasabah')->name('nasabah.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'nasabah'])->name('dashboard');

        // PBI-06: Portal Nasabah (riwayat gabungan & profil mandiri)
        Route::get('/riwayat', [CustomerPortalController::class, 'transactions'])->name('riwayat.index');
        Route::get('/riwayat/setoran/{deposit}', [CustomerPortalController::class, 'showDeposit'])->name('riwayat.deposit');
        Route::get('/riwayat/penarikan/{withdrawal}', [CustomerPortalController::class, 'showWithdrawal'])->name('riwayat.withdrawal');
        Route::get('/profil', [CustomerPortalController::class, 'editProfile'])->name('profil.edit');
        Route::put('/profil', [CustomerPortalController::class, 'updateProfile'])->name('profil.update');

        // PBI-07: Pengajuan Penjemputan Sampah (Nasabah)
        Route::resource('penjemputan', CustomerPickupController::class)
            ->parameters(['penjemputan' => 'pickupRequest'])
            ->only(['index', 'create', 'store', 'show']);

        // PBI-12: Kartu QR Nasabah ("Kartu Saya")
        Route::get('kartu/{customer?}', [CustomerPortalController::class, 'card'])
            ->name('kartu.show');
    });
});
