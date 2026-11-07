<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-ui@banksampah.test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createPetugas(): User
    {
        return User::create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas-ui@banksampah.test',
            'password' => 'password123',
            'role' => 'petugas',
            'is_active' => true,
        ]);
    }

    private function createNasabah(string $code = 'NSB-0001'): User
    {
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => strtolower($code) . '@banksampah.test',
            'password' => 'password123',
            'role' => 'nasabah',
            'is_active' => true,
        ]);

        $customer = new Customer([
            'name' => $user->name,
            'phone' => '081234567890',
            'address' => 'Jl. Kemasan No. 10, Kotagede',
        ]);
        $customer->user_id = $user->id;
        $customer->balance = 9750;
        $customer->is_active = true;
        $customer->save();

        return $user;
    }

    public function test_admin_dashboard_uses_ui_staff_layout_and_enforces_role_access(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        $nasabah = $this->createNasabah();

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-sidebar', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Dashboard Admin')
            ->assertSee('Buka Laporan Bulanan')
            ->assertSee('Data Nasabah')
            ->assertSee('Kelola Petugas')
            ->assertSee('Jenis & Harga Sampah', false)
            ->assertSee('Riwayat Setoran')
            ->assertSee('Riwayat Penarikan')
            ->assertSee('Penjemputan Sampah')
            ->assertSee('Rute Penjemputan')
            ->assertSee('Laporan Bulanan')
            ->assertSee('Dampak')
            ->assertSee('Lihat Semua');

        $this->actingAs($petugas)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_nasabah_dashboard_uses_ui_nasabah_layout_and_enforces_role_access(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        $nasabah = $this->createNasabah();

        $this->get(route('nasabah.dashboard'))->assertRedirect(route('login'));

        $response = $this->actingAs($nasabah)->get(route('nasabah.dashboard'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-nasabah-header', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Portal Nasabah')
            ->assertSee('NSB-0001')
            ->assertSee('Rp 9.750,00')
            ->assertSee('Kartu Saya')
            ->assertSee('Riwayat Lengkap')
            ->assertSee('Ubah Profil')
            ->assertSee('Ajukan Penjemputan')
            ->assertSee('Kontribusi saya')
            ->assertSee('5 Transaksi Terakhir');

        $this->actingAs($admin)->get(route('nasabah.dashboard'))->assertForbidden();
        $this->actingAs($petugas)->get(route('nasabah.dashboard'))->assertForbidden();
    }

    public function test_login_page_uses_ui_guest_layout_and_retains_all_auth_elements(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-guest', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('Masuk')
            ->assertSee('Masuk dengan Google')
            ->assertSee('Daftar dengan Google');

        $admin = $this->createAdmin();
        $this->actingAs($admin)->get(route('login'))->assertRedirect();
    }

    public function test_officer_pickup_page_uses_indonesian_route_sequence_label_and_enforces_role(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        $nasabah = $this->createNasabah();

        $response = $this->actingAs($petugas)->get(route('petugas.penjemputan.index'));

        $response->assertOk()
            ->assertSee('Tugas Penjemputan Terjadwal')
            ->assertSee('Halaman Peta Rute Saya')
            ->assertSee('urutan rute')
            ->assertDontSee('route_sequence');

        $this->actingAs($admin)->get(route('petugas.penjemputan.index'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.penjemputan.index'))->assertForbidden();
    }

    public function test_indonesian_pagination_and_format_helpers(): void
    {
        $admin = $this->createAdmin();

        for ($i = 1; $i <= 12; $i++) {
            $this->createNasabah(sprintf('NSB-%04d', $i));
        }

        $response = $this->actingAs($admin)->get(route('admin.nasabah.index'));

        $response->assertOk()
            ->assertSee('Menampilkan')
            ->assertSee('dari')
            ->assertSee('nasabah')
            ->assertDontSee('Showing');

        $this->assertSame('Rp 9.750,00', format_rupiah(9750));
        $this->assertSame('18-09-2026', format_tanggal('2026-09-18'));
    }
}
