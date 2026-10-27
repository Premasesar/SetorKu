<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\GoogleAuthService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteTwoUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Feature Test PBI-15: Login & Pendaftaran Mandiri dengan Akun Google.
 *
 * Seluruh pemanggilan callback memakai mock Socialite (tidak memanggil Google sungguhan).
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabah;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin.setorku@gmail.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->petugas = User::create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas.setorku@gmail.com',
            'phone' => '081234560001',
            'password' => 'password123',
            'role' => 'petugas',
        ]);

        $this->nasabah = User::create([
            'name' => 'Nasabah Terdaftar',
            'email' => 'nasabah.setorku@gmail.com',
            'phone' => '081234560002',
            'password' => 'password123',
            'role' => 'nasabah',
        ]);

        $this->customer = $this->nasabah->customer()->create([
            'name' => 'Nasabah Terdaftar',
            'address' => 'Jl. Malioboro No. 10, Yogyakarta',
            'phone' => '081234560002',
        ]);
    }

    private function mockGoogleCallbackUser(array $overrides = []): void
    {
        $socialiteUser = SocialiteTwoUser::fake(array_merge([
            'id' => 'google-uid-default',
            'name' => 'Pengguna Google',
            'email' => 'baru@gmail.com',
            'email_verified' => true,
            'token' => 'ya29.fake-access-token-do-not-store',
            'refreshToken' => '1//fake-refresh-token-do-not-store',
        ], $overrides));

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }

    // ─── 1. Login Google berhasil untuk Admin, Petugas, dan Nasabah (termasuk case-insensitive + trim) ───

    public function test_registered_admin_petugas_and_nasabah_can_login_via_google_and_redirect_by_role(): void
    {
        // Admin (uji juga normalisasi spasi + huruf besar dari Google)
        $this->mockGoogleCallbackUser([
            'id' => 'gid-admin-001',
            'name' => 'Admin Utama',
            'email' => '  ADMIN.SETORKU@GMAIL.COM  ',
            'email_verified' => true,
        ]);

        $adminResp = $this->get(route('google.callback'));
        $adminResp->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
        $this->assertSame('gid-admin-001', $this->admin->fresh()->google_id);

        $this->post(route('logout'));

        // Petugas
        $this->mockGoogleCallbackUser([
            'id' => 'gid-petugas-001',
            'name' => 'Petugas Lapangan',
            'email' => 'petugas.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $petugasResp = $this->get(route('google.callback'));
        $petugasResp->assertRedirect(route('petugas.dashboard'));
        $this->assertAuthenticatedAs($this->petugas);
        $this->assertSame('gid-petugas-001', $this->petugas->fresh()->google_id);

        $this->post(route('logout'));

        // Nasabah
        $this->mockGoogleCallbackUser([
            'id' => 'gid-nasabah-001',
            'name' => 'Nasabah Terdaftar',
            'email' => 'nasabah.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $nasabahResp = $this->get(route('google.callback'));
        $nasabahResp->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticatedAs($this->nasabah);
        $this->assertSame('gid-nasabah-001', $this->nasabah->fresh()->google_id);
    }

    // ─── 2. google_id terisi saat login pertama; login kedua dengan google_id sama berhasil ───

    public function test_google_id_is_linked_on_first_login_and_second_login_with_same_google_id_succeeds(): void
    {
        $this->assertNull($this->nasabah->google_id);

        // Login pertama
        $this->mockGoogleCallbackUser([
            'id' => 'gid-tetap-777',
            'email' => 'nasabah.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('nasabah.dashboard'));
        $this->assertSame('gid-tetap-777', $this->nasabah->fresh()->google_id);

        $this->post(route('logout'));
        $this->assertGuest();

        // Login kedua dengan google_id yang sama
        $this->mockGoogleCallbackUser([
            'id' => 'gid-tetap-777',
            'email' => 'nasabah.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticatedAs($this->nasabah);
        $this->assertSame('gid-tetap-777', $this->nasabah->fresh()->google_id);
    }

    // ─── 3. Email sama dengan google_id berbeda dari yang sudah terhubung -> ditolak ───

    public function test_same_email_with_different_google_id_is_rejected(): void
    {
        $this->nasabah->google_id = 'gid-asli-111';
        $this->nasabah->save();

        $this->mockGoogleCallbackUser([
            'id' => 'gid-palsu-999',
            'email' => 'nasabah.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertSame('gid-asli-111', $this->nasabah->fresh()->google_id);
    }

    // ─── 4. Email belum terdaftar -> diarahkan ke /daftar/lengkapi dan belum ada User dibuat ───

    public function test_unregistered_email_redirects_to_complete_registration_without_creating_user_or_storing_tokens(): void
    {
        $initialUserCount = User::count();
        $initialCustomerCount = Customer::count();

        $this->mockGoogleCallbackUser([
            'id' => 'gid-calon-nasabah-01',
            'name' => 'Warga Baru Jogja',
            'email' => 'Warga.Baru@gmail.com',
            'email_verified' => true,
            'token' => 'secret-access-token-xyz',
            'refreshToken' => 'secret-refresh-token-xyz',
        ]);

        $response = $this->get(route('google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('google.register.form'));
        $this->assertSame($initialUserCount, User::count());
        $this->assertSame($initialCustomerCount, Customer::count());

        // Pastikan access_token dan refresh_token TIDAK disimpan di session
        $pending = session(GoogleAuthService::PENDING_SESSION_KEY);
        $this->assertIsArray($pending);
        $this->assertSame('gid-calon-nasabah-01', $pending['google_id']);
        $this->assertSame('warga.baru@gmail.com', $pending['email']);
        $this->assertSame('Warga Baru Jogja', $pending['name']);
        $this->assertArrayNotHasKey('token', $pending);
        $this->assertArrayNotHasKey('access_token', $pending);
        $this->assertArrayNotHasKey('refreshToken', $pending);
        $this->assertArrayNotHasKey('refresh_token', $pending);

        // Halaman /daftar/lengkapi tampil dengan nama & email terisi (email tidak bisa diubah)
        $formResp = $this->get(route('google.register.form'));
        $formResp->assertOk()
            ->assertSee('Lengkapi Pendaftaran')
            ->assertSee('warga.baru@gmail.com')
            ->assertSee('Warga Baru Jogja')
            ->assertSee('readonly', false)
            ->assertSee('disabled', false);
    }

    // ─── 5. Submit form lengkap -> User role nasabah + Customer saldo 0 terbuat, langsung login, ke portal nasabah ───

    public function test_completing_registration_creates_nasabah_user_and_zero_balance_customer_and_logs_in(): void
    {
        $this->mockGoogleCallbackUser([
            'id' => 'gid-nasabah-baru-55',
            'name' => 'Rini Wulandari',
            'email' => 'rini.wulandari@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));

        $submitResp = $this->post(route('google.register.complete'), [
            'name' => 'Rini Wulandari S.',
            'phone' => '081399887766',
            'address' => 'Jl. Kaliurang Km 7, Sleman, Yogyakarta',
            'latitude' => '-7.7520000',
            'longitude' => '110.3820000',
        ]);

        $submitResp->assertRedirect(route('nasabah.dashboard'));

        $newUser = User::where('email', 'rini.wulandari@gmail.com')->first();
        $this->assertNotNull($newUser);
        $this->assertAuthenticatedAs($newUser);
        $this->assertSame('nasabah', $newUser->role);
        $this->assertTrue($newUser->is_active);
        $this->assertSame('gid-nasabah-baru-55', $newUser->google_id);
        $this->assertNull($newUser->password);
        $this->assertNotNull($newUser->email_verified_at);

        $newCustomer = $newUser->customer;
        $this->assertNotNull($newCustomer);
        $this->assertSame('Rini Wulandari S.', $newCustomer->name);
        $this->assertSame('081399887766', $newCustomer->phone);
        $this->assertSame('Jl. Kaliurang Km 7, Sleman, Yogyakarta', $newCustomer->address);
        $this->assertEquals(0.00, (float) $newCustomer->balance);
        $this->assertTrue($newCustomer->is_active);
        $this->assertMatchesRegularExpression('/^NSB-\d{4}$/', $newCustomer->code);

        // Data pendaftaran Google dihapus dari session setelah selesai
        $this->assertNull(session(GoogleAuthService::PENDING_SESSION_KEY));
    }

    // ─── 6. Field role/email/google_id/saldo/is_active yang dikirim lewat form diabaikan ───

    public function test_tampered_role_email_google_id_and_balance_inputs_are_ignored(): void
    {
        $this->mockGoogleCallbackUser([
            'id' => 'gid-asli-session-88',
            'name' => 'Calon Jujur',
            'email' => 'calon.jujur@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));

        $response = $this->post(route('google.register.complete'), [
            'name' => 'Calon Jujur',
            'phone' => '081277778888',
            'address' => 'Jl. Gejayan No. 5, Sleman',
            'role' => 'admin',
            'email' => 'hacker@evil.com',
            'google_id' => 'gid-hacker-override',
            'balance' => 9999999,
            'saldo' => 9999999,
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('nasabah.dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'hacker@evil.com']);

        $createdUser = User::where('email', 'calon.jujur@gmail.com')->firstOrFail();
        $this->assertSame('nasabah', $createdUser->role);
        $this->assertSame('gid-asli-session-88', $createdUser->google_id);
        $this->assertTrue($createdUser->is_active);

        $createdCustomer = $createdUser->customer()->firstOrFail();
        $this->assertEquals(0.00, (float) $createdCustomer->balance);
        $this->assertTrue($createdCustomer->is_active);
    }

    // ─── 7. /daftar/lengkapi tanpa session Google -> redirect ke login; session kedaluwarsa -> ditolak ───

    public function test_complete_registration_without_session_or_with_expired_session_is_rejected(): void
    {
        // Tanpa session Google (GET & POST)
        $this->get(route('google.register.form'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->post(route('google.register.complete'), [
            'name' => 'Tanpa Sesi',
            'phone' => '08123456789',
            'address' => 'Jl. Tanpa Sesi',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        // Session Google kedaluwarsa (> 15 menit)
        Carbon::setTestNow(Carbon::parse('2026-10-28 10:00:00'));

        $this->mockGoogleCallbackUser([
            'id' => 'gid-exp-001',
            'name' => 'Pengguna Lambat',
            'email' => 'lambat@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));

        Carbon::setTestNow(Carbon::parse('2026-10-28 10:16:00'));

        $expiredPost = $this->post(route('google.register.complete'), [
            'name' => 'Pengguna Lambat',
            'phone' => '08123456789',
            'address' => 'Jl. Kedaluwarsa No. 1',
        ]);

        $this->assertGuest();
        $expiredPost->assertRedirect(route('login'));
        $expiredPost->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'lambat@gmail.com']);
        $this->assertNull(session(GoogleAuthService::PENDING_SESSION_KEY));

        Carbon::setTestNow();
    }

    // ─── 8. email_verified false -> ditolak ───

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->mockGoogleCallbackUser([
            'id' => 'gid-unverified-01',
            'email' => 'admin.setorku@gmail.com',
            'email_verified' => false,
        ]);

        $response = $this->get(route('google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertNull($this->admin->fresh()->google_id);
    }

    // ─── 9. Nasabah nonaktif dan petugas nonaktif -> ditolak ───

    public function test_inactive_customer_and_inactive_officer_are_rejected_on_google_login(): void
    {
        // Nonaktifkan nasabah melalui customers.is_active = false
        $this->customer->is_active = false;
        $this->customer->save();

        $this->mockGoogleCallbackUser([
            'id' => 'gid-nonaktif-nsb',
            'email' => 'nasabah.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $nasabahResp = $this->get(route('google.callback'));
        $this->assertGuest();
        $nasabahResp->assertRedirect(route('login'));
        $nasabahResp->assertSessionHasErrors([
            'email' => 'Akun dinonaktifkan. Hubungi admin.',
        ]);

        // Nonaktifkan petugas melalui users.is_active = false
        $this->petugas->is_active = false;
        $this->petugas->save();

        $this->mockGoogleCallbackUser([
            'id' => 'gid-nonaktif-ptg',
            'email' => 'petugas.setorku@gmail.com',
            'email_verified' => true,
        ]);

        $petugasResp = $this->get(route('google.callback'));
        $this->assertGuest();
        $petugasResp->assertRedirect(route('login'));
        $petugasResp->assertSessionHasErrors([
            'email' => 'Akun dinonaktifkan. Hubungi admin.',
        ]);
    }

    // ─── 10. Email sudah dipakai saat submit (race condition) -> ditolak, tidak ada duplikat ───

    public function test_race_condition_when_email_is_registered_before_submit_is_rejected_without_duplicate(): void
    {
        $this->mockGoogleCallbackUser([
            'id' => 'gid-race-100',
            'name' => 'Warga Balapan',
            'email' => 'balapan@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));

        // Simulasikan Admin/proses lain mendaftarkan email yang sama tepat sebelum form disubmit
        $existing = User::create([
            'name' => 'Sudah Ada Duluan',
            'email' => 'balapan@gmail.com',
            'password' => 'password123',
            'role' => 'nasabah',
        ]);
        $existing->customer()->create([
            'name' => 'Sudah Ada Duluan',
            'address' => 'Jl. Duluan No. 1',
            'phone' => '081111111111',
        ]);

        $response = $this->post(route('google.register.complete'), [
            'name' => 'Warga Balapan',
            'phone' => '082222222222',
            'address' => 'Jl. Menyusul No. 2',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'balapan@gmail.com')->count());
        $this->assertSame('Sudah Ada Duluan', User::where('email', 'balapan@gmail.com')->first()->name);
    }

    // ─── 11. Nasabah hasil pendaftaran Google tidak bisa membuka rute Admin/Petugas ───

    public function test_google_registered_customer_cannot_access_admin_or_officer_routes(): void
    {
        $this->mockGoogleCallbackUser([
            'id' => 'gid-rbac-test',
            'name' => 'Nasabah Google Baru',
            'email' => 'nasabah.baru.rbac@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));

        $this->post(route('google.register.complete'), [
            'name' => 'Nasabah Google Baru',
            'phone' => '081355556666',
            'address' => 'Jl. Kusumanegara No. 20, Yogyakarta',
        ])->assertRedirect(route('nasabah.dashboard'));

        $this->get(route('nasabah.dashboard'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.petugas.index'))->assertForbidden();
        $this->get(route('petugas.dashboard'))->assertForbidden();
        $this->get(route('petugas.setoran.create'))->assertForbidden();
    }

    // ─── 12. Login email+password lama tetap berfungsi ───

    public function test_legacy_email_and_password_login_still_works_for_all_roles(): void
    {
        $this->post('/login', [
            'email' => 'admin.setorku@gmail.com',
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));

        $this->post('/login', [
            'email' => 'petugas.setorku@gmail.com',
            'password' => 'password123',
        ])->assertRedirect(route('petugas.dashboard'));
        $this->post(route('logout'));

        $this->post('/login', [
            'email' => 'nasabah.setorku@gmail.com',
            'password' => 'password123',
        ])->assertRedirect(route('nasabah.dashboard'));

        // Akun tanpa password lokal (pendaftaran Google) ditolak rapi bila mencoba form password
        $this->post(route('logout'));
        $googleOnlyUser = User::create([
            'name' => 'Akun Google Murni',
            'email' => 'murni.google@gmail.com',
            'password' => null,
            'role' => 'nasabah',
        ]);
        $googleOnlyUser->google_id = 'gid-murni-01';
        $googleOnlyUser->save();

        $this->post('/login', [
            'email' => 'murni.google@gmail.com',
            'password' => 'tebak-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ─── Tambahan: OAuth state bawaan, error callback (InvalidState, access_denied), Kelola Petugas tanpa password & SEED_ADMIN_EMAIL ───

    public function test_redirect_to_google_uses_stateful_oauth_and_login_page_shows_inline_svg_google_buttons(): void
    {
        $loginPage = $this->get(route('login'));
        $loginPage->assertOk()
            ->assertSee('Masuk dengan Google')
            ->assertSee('Belum punya akun?')
            ->assertSee('Daftar dengan Google')
            ->assertSee('<svg', false)
            ->assertSee(route('google.redirect'), false);

        $redirect = $this->get(route('google.redirect'));
        $redirect->assertRedirect();
        $targetUrl = (string) $redirect->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $targetUrl);
        $this->assertStringContainsString('state=', $targetUrl);
        $this->assertNotEmpty(session('state'));
    }

    public function test_invalid_state_cancel_and_unexpected_exceptions_redirect_to_login_gracefully(): void
    {
        // Pembatalan pengguna (access_denied)
        $cancelResp = $this->get(route('google.callback', ['error' => 'access_denied']));
        $this->assertGuest();
        $cancelResp->assertRedirect(route('login'));
        $cancelResp->assertSessionHasErrors('email');

        // InvalidStateException dari Socialite
        $providerInvalidState = Mockery::mock(Provider::class);
        $providerInvalidState->shouldReceive('user')->once()->andThrow(new InvalidStateException());
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($providerInvalidState);

        $stateResp = $this->get(route('google.callback'));
        $this->assertGuest();
        $stateResp->assertRedirect(route('login'));
        $stateResp->assertSessionHasErrors('email');

        // Exception tak terduga saat pertukaran token
        $providerError = Mockery::mock(Provider::class);
        $providerError->shouldReceive('user')->once()->andThrow(new RuntimeException('Network error'));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($providerError);

        $errResp = $this->get(route('google.callback'));
        $this->assertGuest();
        $errResp->assertRedirect(route('login'));
        $errResp->assertSessionHasErrors('email');
    }

    public function test_admin_can_create_officer_without_password_using_google_email_and_officer_can_login_via_google(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.petugas.create'))
            ->assertOk()
            ->assertSee('Email akun Google petugas');

        $storeResp = $this->actingAs($this->admin)->post(route('admin.petugas.store'), [
            'name' => 'Petugas Tanpa Password',
            'email' => 'petugas.google@gmail.com',
            'phone' => '081200009999',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $officer = User::where('email', 'petugas.google@gmail.com')->first();
        $this->assertNotNull($officer);
        $this->assertSame('petugas', $officer->role);
        $this->assertNull($officer->password);
        $storeResp->assertRedirect(route('admin.petugas.show', $officer));

        $this->post(route('logout'));

        // Petugas langsung login dengan Google memakai email tersebut
        $this->mockGoogleCallbackUser([
            'id' => 'gid-petugas-baru-88',
            'name' => 'Petugas Tanpa Password',
            'email' => 'petugas.google@gmail.com',
            'email_verified' => true,
        ]);

        $this->get(route('google.callback'))->assertRedirect(route('petugas.dashboard'));
        $this->assertAuthenticatedAs($officer);
        $this->assertSame('gid-petugas-baru-88', $officer->fresh()->google_id);
    }

    public function test_database_seeder_reads_seed_admin_email_from_config(): void
    {
        config(['setorku.seed_admin_email' => 'pemilik.asli@gmail.com']);

        $this->seed(DatabaseSeeder::class);

        $seededAdmin = User::where('email', 'pemilik.asli@gmail.com')->first();
        $this->assertNotNull($seededAdmin);
        $this->assertSame('admin', $seededAdmin->role);
    }
}
