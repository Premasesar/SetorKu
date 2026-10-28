<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteTwoUser;
use Mockery;
use Tests\TestCase;

/**
 * Feature Test PBI-16: Pilih Titik Lokasi Rumah lewat Peta (Leaflet + OSM)
 * pada halaman Lengkapi Pendaftaran Nasabah via Google.
 *
 * Callback Google memakai mock Socialite (tidak memanggil Google sungguhan).
 */
class RegistrationMapPickerTest extends TestCase
{
    use RefreshDatabase;

    private function startGoogleRegistration(string $email = 'peta.baru@gmail.com', string $googleId = 'gid-peta-001'): void
    {
        $socialiteUser = SocialiteTwoUser::fake([
            'id' => $googleId,
            'name' => 'Calon Nasabah Peta',
            'email' => $email,
            'email_verified' => true,
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));
    }

    private function validProfile(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Calon Nasabah Peta',
            'phone' => '081234567001',
            'address' => 'Jl. Malioboro No. 1, Yogyakarta',
        ], $overrides);
    }

    // 1. Halaman menampilkan kontainer peta + input latitude/longitude readonly
    public function test_complete_registration_page_renders_map_picker_with_readonly_coordinate_inputs(): void
    {
        $this->startGoogleRegistration();

        $response = $this->get(route('google.register.form'));
        $response->assertOk()
            ->assertSee('Titik Lokasi Rumah (opsional)')
            ->assertSee('Klik pada peta atau geser pin ke lokasi rumah Anda.')
            ->assertSee('data-map-picker', false)
            ->assertSee('data-map-picker-canvas', false)
            ->assertSee('height: 320px;', false)
            ->assertSee('data-default-lat="-7.797068"', false)
            ->assertSee('data-default-lng="110.370529"', false)
            ->assertSee('data-default-zoom="13"', false)
            ->assertSee('data-selected-zoom="17"', false)
            ->assertSee('Gunakan Lokasi Saya')
            ->assertSee('Hapus Titik')
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', false)
            ->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', false)
            ->assertSee('js/map-picker.js', false);

        $html = $response->getContent();

        // Peta berada di antara kolom Alamat Lengkap dan kolom Latitude/Longitude
        $addressPos = strpos($html, 'id="address"');
        $mapPos = strpos($html, 'data-map-picker-canvas');
        $latPos = strpos($html, 'name="latitude"');
        $this->assertNotFalse($addressPos);
        $this->assertTrue($addressPos < $mapPos && $mapPos < $latPos, 'Peta harus berada di antara Alamat dan Latitude/Longitude.');

        // Input koordinat readonly dan awalnya kosong
        $this->assertMatchesRegularExpression('/<input[^>]*name="latitude"[^>]*readonly[^>]*value=""/s', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="longitude"[^>]*readonly[^>]*value=""/s', $html);

        // Aset Leaflet & skrip pemilih peta dimuat tepat sekali
        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.js'));
        $this->assertSame(1, substr_count($html, 'data-setorku-map-picker-script'));
        $this->assertFileExists(public_path('js/map-picker.js'));
    }

    // 2. Submit dengan koordinat valid -> tersimpan
    public function test_valid_coordinates_are_saved_to_customer(): void
    {
        $this->startGoogleRegistration();

        $this->post(route('google.register.complete'), $this->validProfile([
            'latitude' => '-7.7828750',
            'longitude' => '110.3670830',
        ]))->assertRedirect(route('nasabah.dashboard'));

        $customer = User::where('email', 'peta.baru@gmail.com')->firstOrFail()->customer;
        $this->assertNotNull($customer);
        $this->assertSame('-7.7828750', (string) $customer->latitude);
        $this->assertSame('110.3670830', (string) $customer->longitude);
    }

    // 3. Submit tanpa koordinat -> berhasil, kolom null
    public function test_registration_without_coordinates_succeeds_with_null_columns(): void
    {
        $this->startGoogleRegistration();

        $this->post(route('google.register.complete'), $this->validProfile([
            'latitude' => '',
            'longitude' => '',
        ]))->assertRedirect(route('nasabah.dashboard'));

        $customer = User::where('email', 'peta.baru@gmail.com')->firstOrFail()->customer;
        $this->assertNull($customer->latitude);
        $this->assertNull($customer->longitude);
    }

    // 4. Hanya latitude atau hanya longitude -> ditolak, tidak ada akun dibuat, session tetap
    public function test_only_latitude_or_only_longitude_is_rejected(): void
    {
        $this->startGoogleRegistration();

        $this->from(route('google.register.form'))
            ->post(route('google.register.complete'), $this->validProfile(['latitude' => '-7.7828750']))
            ->assertRedirect(route('google.register.form'))
            ->assertSessionHasErrors(['longitude' => 'Kolom longitude wajib diisi jika latitude diisi.']);

        $this->from(route('google.register.form'))
            ->post(route('google.register.complete'), $this->validProfile(['longitude' => '110.3670830']))
            ->assertRedirect(route('google.register.form'))
            ->assertSessionHasErrors(['latitude' => 'Kolom latitude wajib diisi jika longitude diisi.']);

        $this->assertDatabaseMissing('users', ['email' => 'peta.baru@gmail.com']);
        $this->assertSame(0, Customer::count());
        $this->assertIsArray(session(GoogleAuthService::PENDING_SESSION_KEY));
    }

    // 5. Di luar rentang / bukan angka -> ditolak dengan pesan Bahasa Indonesia
    public function test_out_of_range_or_non_numeric_coordinates_are_rejected(): void
    {
        $this->startGoogleRegistration();

        $this->from(route('google.register.form'))
            ->post(route('google.register.complete'), $this->validProfile([
                'latitude' => '91',
                'longitude' => '-181',
            ]))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude harus bernilai antara -90 sampai 90.',
                'longitude' => 'Kolom longitude harus bernilai antara -180 sampai 180.',
            ]);

        $this->from(route('google.register.form'))
            ->post(route('google.register.complete'), $this->validProfile([
                'latitude' => 'abc',
                'longitude' => '110.36<script>',
            ]))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude harus berupa angka.',
                'longitude' => 'Kolom longitude harus berupa angka.',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'peta.baru@gmail.com']);
    }

    // 6. Setelah validasi gagal, nilai koordinat lama terisi kembali agar peta memusatkan pin
    public function test_old_coordinates_are_refilled_after_failed_validation(): void
    {
        $this->startGoogleRegistration();

        $response = $this->followingRedirects()
            ->from(route('google.register.form'))
            ->post(route('google.register.complete'), $this->validProfile([
                'phone' => '',
                'latitude' => '-7.8012345',
                'longitude' => '110.3654321',
            ]));

        $response->assertOk()
            ->assertSee('Lengkapi Pendaftaran')
            ->assertSee('value="-7.8012345"', false)
            ->assertSee('value="110.3654321"', false)
            ->assertSee('Kolom nomor HP wajib diisi.');
    }

    // 7. Komponen peta tidak dipasang di form Tambah/Ubah Nasabah milik Admin
    public function test_map_picker_component_is_not_installed_on_other_customer_forms(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin']);
        $nasabah = User::create(['name' => 'Nasabah', 'email' => 'nasabah@test.com', 'password' => 'password', 'role' => 'nasabah']);
        $customer = $nasabah->customer()->create(['name' => 'Nasabah', 'address' => 'Jl. Kaliurang No. 2']);

        $this->actingAs($admin)->get(route('admin.nasabah.create'))->assertOk()->assertDontSee('data-map-picker', false);
        $this->actingAs($admin)->get(route('admin.nasabah.edit', $customer))->assertOk()->assertDontSee('data-map-picker', false);
    }
}
