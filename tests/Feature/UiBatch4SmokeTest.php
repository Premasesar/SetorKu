<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Services\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiBatch4SmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabahUser;
    private Customer $customer;
    private WasteType $wasteType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@setorku.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->petugas = User::create([
            'name' => 'Bambang Sugianto',
            'email' => 'bambang.petugas@gmail.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $this->nasabahUser = User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@setorku.test',
            'password' => 'password123',
            'role' => 'nasabah',
        ]);

        $this->customer = $this->nasabahUser->customer()->create([
            'name' => 'Siti Rahayu',
            'phone' => '081298765432',
            'address' => 'Jl. Kaliurang KM 5, Sleman',
            'latitude' => -7.7654321,
            'longitude' => 110.3789123,
        ]);

        $this->wasteType = WasteType::create([
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 4500.00,
        ]);
    }

    public function test_officers_index_create_edit_and_show_render_ui_staff_and_enforce_admin_access(): void
    {
        // 1. Index
        $this->actingAs($this->admin)
            ->get(route('admin.petugas.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Kelola Petugas')
            ->assertSee('Tambah Petugas')
            ->assertSee('Cari nama, email, nomor HP, atau kode petugas...', false)
            ->assertSee($this->petugas->officer_code)
            ->assertSee('Bambang Sugianto')
            ->assertSee('bambang.petugas@gmail.com')
            ->assertSee('081234567890')
            ->assertSee('Detail')
            ->assertSee('Ubah');

        // 2. Create
        $this->actingAs($this->admin)
            ->get(route('admin.petugas.create'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Tambah Akun Petugas')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Simpan Petugas')
            ->assertSee('Batal');

        // 3. Edit
        $this->actingAs($this->admin)
            ->get(route('admin.petugas.edit', $this->petugas))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Ubah Data Petugas')
            ->assertSee('Bambang Sugianto')
            ->assertSee('Simpan Perubahan')
            ->assertSee('Batal');

        // 4. Show
        $this->actingAs($this->admin)
            ->get(route('admin.petugas.show', $this->petugas))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($this->petugas->officer_code)
            ->assertSee('Bambang Sugianto')
            ->assertSee('Nonaktifkan')
            ->assertSee('Reset Password Petugas')
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Reset Password');

        // Peran lain ditolak
        $this->actingAs($this->petugas)->get(route('admin.petugas.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.petugas.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.petugas.index'))->assertRedirect(route('login'));
    }

    public function test_pickups_admin_index_and_show_render_ui_staff_with_all_states_and_elements(): void
    {
        $pickupService = app(PickupService::class);
        $pickupWaiting = $pickupService->submitRequest($this->customer, [
            'requested_date' => now()->toDateString(),
            'address' => $this->customer->address,
            'latitude' => $this->customer->latitude,
            'longitude' => $this->customer->longitude,
            'notes' => 'Mohon dijemput pagi',
        ]);

        // Index
        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Kelola Penjemputan Sampah')
            ->assertSee('Cari kode JMP atau nama nasabah...', false)
            ->assertSee('Semua Status')
            ->assertSee('Filter')
            ->assertSee($pickupWaiting->code)
            ->assertSee('Detail & Kelola', false);

        // Show (status Menunggu -> form Jadwalkan & Tolak)
        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.show', $pickupWaiting))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($pickupWaiting->code)
            ->assertSee('081298765432')
            ->assertSee('Mohon dijemput pagi')
            ->assertSee('admin-pickup-map', false)
            ->assertSee('Jadwalkan Penjemputan')
            ->assertSee('Simpan Jadwal')
            ->assertSee('Tolak Pengajuan')
            ->assertSee('name="rejection_reason"', false);

        // Show (status Terjadwal -> form Perbarui Jadwal)
        $pickupScheduled = $pickupService->schedule(
            pickupRequest: $pickupWaiting,
            scheduledDate: now()->toDateString(),
            officerId: $this->petugas->id,
            admin: $this->admin,
        );

        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.show', $pickupScheduled))
            ->assertOk()
            ->assertSee('Ubah Jadwal / Petugas Penjemputan')
            ->assertSee('Perbarui Jadwal');

        // Show (status Selesai -> tabel bukti setoran hasil penjemputan)
        $pickupService->completePickup($pickupScheduled, [
            ['waste_type_id' => $this->wasteType->id, 'weight' => 2.500],
        ], $this->petugas);

        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.show', $pickupScheduled->fresh()))
            ->assertOk()
            ->assertSee('Daftar Bukti Setoran Hasil Penjemputan')
            ->assertSee('Plastik PET')
            ->assertSee('Bukti');

        $this->actingAs($this->petugas)->get(route('admin.penjemputan.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.penjemputan.index'))->assertForbidden();
    }

    public function test_routes_reports_and_impact_admin_views_render_ui_staff_with_all_elements(): void
    {
        $pickupService = app(PickupService::class);
        $today = now()->toDateString();

        $p1 = $pickupService->submitRequest($this->customer, [
            'requested_date' => $today,
            'address' => 'Titik Jemput 1',
            'latitude' => -7.7800000,
            'longitude' => 110.3700000,
        ]);
        $p1 = $pickupService->schedule($p1, $today, $this->petugas->id, $this->admin);

        $p2 = $pickupService->submitRequest($this->customer, [
            'requested_date' => $today,
            'address' => 'Titik Jemput 2',
            'latitude' => -7.8050000,
            'longitude' => 110.3600000,
        ]);
        $p2 = $pickupService->schedule($p2, $today, $this->petugas->id, $this->admin);

        // Buat 1 titik dengan koordinat tidak valid agar tabel Titik Dikecualikan teruji
        $pInvalid = $pickupService->submitRequest($this->customer, [
            'requested_date' => $today,
            'address' => 'Titik Koordinat Rusak',
            'latitude' => -7.7900000,
            'longitude' => 110.3650000,
        ]);
        $pInvalid = $pickupService->schedule($pInvalid, $today, $this->petugas->id, $this->admin);
        PickupRequest::whereKey($pInvalid->id)->update(['latitude' => 0, 'longitude' => 0]);

        // 1. Routes Admin (dengan hasil perhitungan)
        $this->actingAs($this->admin)
            ->get(route('admin.rute.index', ['date' => $today, 'officer_id' => $this->petugas->id]))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Optimasi Rute Penjemputan')
            ->assertSee('Catatan Metodologi:')
            ->assertSee('Haversine')
            ->assertSee('Nearest Neighbor + 2-Opt')
            ->assertSee('Hitung rute')
            ->assertSee('Daftar Penjemputan')
            ->assertSee('Titik Dikecualikan dari Perhitungan')
            ->assertSee('Urutan Awal (Waktu Masuk)')
            ->assertSee('Hasil Nearest Neighbor')
            ->assertSee('Hasil Nearest Neighbor + 2-Opt')
            ->assertSee('Penghematan vs Urutan Awal')
            ->assertSee('Simpan urutan rute')
            ->assertSee('admin-route-map', false)
            ->assertSee('Jarak Kumulatif (km)');

        // 2. Reports Index
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Laporan Rekap Bulanan')
            ->assertSee('name="month"', false)
            ->assertSee('name="year"', false)
            ->assertSee('Tampilkan')
            ->assertSee('1. Rekap Setoran per Jenis Sampah')
            ->assertSee('2. Rekap Penarikan Saldo')
            ->assertSee('3. Rekap Penjemputan per Status')
            ->assertSee('Total Pengajuan')
            ->assertDontSee('Unduh PDF')
            ->assertDontSee('Ekspor PDF');

        // 3. Impact Index
        $this->actingAs($this->admin)
            ->get(route('admin.dampak.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Dampak Pengelolaan Sampah')
            ->assertSee('Total Sampah Terkelola (Seluruh Waktu)')
            ->assertSee('Total Nilai Setoran (Seluruh Waktu)')
            ->assertSee('Jumlah Nasabah Aktif')
            ->assertSee('monthlyWeightBarChart', false)
            ->assertSee('Komposisi Sampah Terkelola per Jenis Sampah')
            ->assertSee('Laporan Bulanan')
            ->assertDontSee('CO₂')
            ->assertDontSee('CO2');

        // Peran lain ditolak
        $this->actingAs($this->petugas)->get(route('admin.rute.index'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('admin.laporan.index'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('admin.dampak.index'))->assertForbidden();
    }
}
