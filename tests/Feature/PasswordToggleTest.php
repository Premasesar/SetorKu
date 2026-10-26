<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test: Fitur Lihat/Sembunyikan Password (Ikon Mata) di Seluruh Kolom Password Aplikasi.
 */
class PasswordToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@test.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->petugas = User::create([
            'name' => 'Petugas Satu',
            'email' => 'petugas@test.com',
            'password' => 'password',
            'role' => 'petugas',
        ]);
    }

    public function test_login_page_renders_password_input_with_accessible_toggle_button_and_standalone_script(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('id="password"', false)
            ->assertSee('type="password"', false)
            ->assertSee('bi-lock', false)
            ->assertSee('type="button"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('aria-label="Tampilkan password"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('bi-eye', false)
            ->assertSee('Sembunyikan password', false)
            ->assertSee('bi-eye-slash', false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '<script data-setorku-password-script>'));

        // Setelah gagal validasi login, password tetap tersembunyi secara default
        $afterFail = $this->followingRedirects()->from(route('login'))->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'salah-sekali',
        ]);

        $afterFail->assertOk()
            ->assertSee('type="password"', false)
            ->assertSee('aria-label="Tampilkan password"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertDontSee('salah-sekali');
    }

    public function test_all_customer_and_officer_password_pages_render_toggles_once_per_page_and_preserve_validation(): void
    {
        $pagesWithTwoPasswordFields = [
            route('admin.nasabah.create') => $this->admin,
            route('petugas.nasabah.create') => $this->petugas,
            route('admin.petugas.create') => $this->admin,
            route('admin.petugas.show', $this->petugas) => $this->admin,
        ];

        foreach ($pagesWithTwoPasswordFields as $url => $actor) {
            $response = $this->actingAs($actor)->get($url);

            $response->assertOk()
                ->assertSee('name="password"', false)
                ->assertSee('name="password_confirmation"', false)
                ->assertSee('data-password-toggle="password"', false)
                ->assertSee('data-password-toggle="password_confirmation"', false)
                ->assertSee('aria-label="Tampilkan password"', false)
                ->assertSee('aria-pressed="false"', false);

            $html = $response->getContent();
            // Dua tombol toggle (password + password_confirmation), tetapi skrip JS hanya dimuat sekali (@once)
            $this->assertSame(2, substr_count($html, 'aria-label="Tampilkan password"'), "Jumlah tombol toggle tidak sesuai pada {$url}");
            $this->assertSame(1, substr_count($html, '<script data-setorku-password-script>'), "Skrip @once terduplikasi pada {$url}");
        }

        // Pastikan is-invalid & invalid-feedback tetap tampil saat validasi password gagal dan default tetap type="password"
        $failedCreate = $this->actingAs($this->admin)
            ->followingRedirects()
            ->from(route('admin.nasabah.create'))
            ->post(route('admin.nasabah.store'), [
                'name' => 'Warga Baru',
                'address' => 'Jl. Malioboro No. 99',
                'email' => 'wargabaru@test.com',
                'password' => '123',
                'password_confirmation' => '456',
            ]);

        $failedCreate->assertOk()
            ->assertSee('is-invalid', false)
            ->assertSee('invalid-feedback', false)
            ->assertSee('type="password"', false)
            ->assertSee('aria-label="Tampilkan password"', false);
    }
}
