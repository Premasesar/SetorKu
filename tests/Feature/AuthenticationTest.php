<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acceptance Test PBI-01: Authentication & Role.
 *
 * Memverifikasi:
 * - AC1: Login berhasil dengan kredensial valid
 * - AC2: Kredensial salah menampilkan error, tetap di halaman login
 * - AC3: Admin diarahkan ke Dashboard Admin
 * - AC4: Petugas diarahkan ke Dashboard Petugas
 * - AC5: Nasabah diarahkan ke Portal Nasabah
 * - AC6: Admin tidak dapat mengakses route Petugas/Nasabah
 * - AC7: Petugas tidak dapat mengakses route Admin/Nasabah
 * - AC8: Nasabah tidak dapat mengakses route Admin/Petugas
 * - AC9: Proteksi dilakukan di backend menggunakan middleware
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user dummy sesuai seeder PBI-01
        User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Petugas Test',
            'email' => 'petugas@test.com',
            'password' => bcrypt('password'),
            'role' => 'petugas',
        ]);

        User::create([
            'name' => 'Nasabah Test',
            'email' => 'nasabah@test.com',
            'password' => bcrypt('password'),
            'role' => 'nasabah',
        ]);
    }

    // ─── AC1: Login berhasil dengan kredensial valid ─────────────

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_petugas_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'petugas@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('petugas.dashboard'));
    }

    public function test_nasabah_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'nasabah@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('nasabah.dashboard'));
    }

    // ─── AC2: Kredensial salah menampilkan error ────────────────

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ─── AC3: Admin diarahkan ke Dashboard Admin ────────────────

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
    }

    // ─── AC4: Petugas diarahkan ke Dashboard Petugas ────────────

    public function test_petugas_can_access_petugas_dashboard(): void
    {
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/petugas/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Petugas');
    }

    // ─── AC5: Nasabah diarahkan ke Portal Nasabah ───────────────

    public function test_nasabah_can_access_nasabah_dashboard(): void
    {
        $nasabah = User::where('role', 'nasabah')->first();

        $response = $this->actingAs($nasabah)->get('/nasabah/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Portal Nasabah');
    }

    // ─── AC6: Admin tidak dapat mengakses route Petugas/Nasabah ─

    public function test_admin_cannot_access_petugas_dashboard(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/petugas/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_cannot_access_nasabah_dashboard(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/nasabah/dashboard');

        $response->assertStatus(403);
    }

    // ─── AC7: Petugas tidak dapat mengakses route Admin/Nasabah ─

    public function test_petugas_cannot_access_admin_dashboard(): void
    {
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_petugas_cannot_access_nasabah_dashboard(): void
    {
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/nasabah/dashboard');

        $response->assertStatus(403);
    }

    // ─── AC8: Nasabah tidak dapat mengakses route Admin/Petugas ─

    public function test_nasabah_cannot_access_admin_dashboard(): void
    {
        $nasabah = User::where('role', 'nasabah')->first();

        $response = $this->actingAs($nasabah)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_nasabah_cannot_access_petugas_dashboard(): void
    {
        $nasabah = User::where('role', 'nasabah')->first();

        $response = $this->actingAs($nasabah)->get('/petugas/dashboard');

        $response->assertStatus(403);
    }

    // ─── AC9: Proteksi middleware (guest tidak bisa akses) ──────

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_petugas_dashboard(): void
    {
        $response = $this->get('/petugas/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_nasabah_dashboard(): void
    {
        $response = $this->get('/nasabah/dashboard');

        $response->assertRedirect(route('login'));
    }

    // ─── Halaman login tampil untuk guest ───────────────────────

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SetorKu');
    }

    // ─── Logout berfungsi ───────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
