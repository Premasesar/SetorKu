<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-17: Peta Pemilih Titik Lokasi (Leaflet + OSM)
 * pada halaman Profil Saya Nasabah (/nasabah/profil).
 */
class ProfileMapPickerTest extends TestCase
{
    use RefreshDatabase;

    private User $userWithCoords;
    private Customer $customerWithCoords;
    private User $userWithoutCoords;
    private Customer $customerWithoutCoords;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userWithCoords = User::create([
            'name' => 'Nasabah Koordinat',
            'email' => 'koordinat@test.com',
            'password' => 'password',
            'role' => 'nasabah',
        ]);
        $this->customerWithCoords = $this->userWithCoords->customer()->create([
            'name' => 'Nasabah Koordinat',
            'address' => 'Jl. Kaliurang Km 5, Sleman',
            'phone' => '081234567890',
            'latitude' => '-7.7654321',
            'longitude' => '110.3789012',
        ]);

        $this->userWithoutCoords = User::create([
            'name' => 'Nasabah Tanpa Titik',
            'email' => 'tanpatitik@test.com',
            'password' => 'password',
            'role' => 'nasabah',
        ]);
        $this->customerWithoutCoords = $this->userWithoutCoords->customer()->create([
            'name' => 'Nasabah Tanpa Titik',
            'address' => 'Jl. Parangtritis Km 4, Bantul',
            'phone' => '089876543210',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    // 1. Halaman Profil Saya menampilkan kontainer peta, input koordinat readonly, dan memuat Leaflet tepat sekali
    public function test_profile_page_renders_map_picker_with_readonly_inputs_and_loads_leaflet_once(): void
    {
        $response = $this->actingAs($this->userWithoutCoords)->get(route('nasabah.profil.edit'));

        $response->assertOk()
            ->assertSee('Titik Lokasi Rumah (opsional)')
            ->assertSee('Klik pada peta atau geser pin ke lokasi rumah Anda.')
            ->assertSee('id="profile-location-map"', false)
            ->assertSee('data-map-picker', false)
            ->assertSee('data-map-picker-canvas', false)
            ->assertSee('height: 320px;', false)
            ->assertSee('data-default-lat="-7.797068"', false)
            ->assertSee('data-default-lng="110.370529"', false)
            ->assertSee('data-default-zoom="13"', false)
            ->assertSee('data-selected-zoom="17"', false)
            ->assertSee('Gunakan Lokasi Saya')
            ->assertSee('Hapus Titik')
            ->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', false)
            ->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', false)
            ->assertSee('js/map-picker.js', false);

        $html = $response->getContent();

        // Peta berada di antara kolom Alamat dan kolom Latitude/Longitude
        $addressPos = strpos($html, 'id="address"');
        $mapPos = strpos($html, 'data-map-picker-canvas');
        $latPos = strpos($html, 'name="latitude"');
        $this->assertNotFalse($addressPos);
        $this->assertTrue(
            $addressPos < $mapPos && $mapPos < $latPos,
            'Peta harus berada di antara kolom Alamat dan kolom Latitude/Longitude.'
        );

        // Nasabah tanpa titik tersimpan: kedua input readonly dan kosong
        $this->assertMatchesRegularExpression('/<input[^>]*name="latitude"[^>]*readonly[^>]*value=""/s', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="longitude"[^>]*readonly[^>]*value=""/s', $html);

        // Aset Leaflet CSS, JS, dan skrip komponen hanya dimuat tepat sekali
        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.css'));
        $this->assertSame(1, substr_count($html, 'leaflet@1.9.4/dist/leaflet.js'));
        $this->assertSame(1, substr_count($html, 'data-setorku-map-picker-script'));
    }

    // 2. Nasabah yang sudah punya koordinat: nilai tersimpan dirender sebagai nilai awal
    public function test_saved_customer_coordinates_are_rendered_as_initial_values(): void
    {
        $response = $this->actingAs($this->userWithCoords)->get(route('nasabah.profil.edit'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*name="latitude"[^>]*readonly[^>]*value="-7\.7654321"/s', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="longitude"[^>]*readonly[^>]*value="110\.3789012"/s', $html);
    }

    // 3. Pembaruan dengan koordinat valid -> tersimpan di tabel customers
    public function test_updating_profile_with_valid_coordinates_saves_to_database(): void
    {
        $this->actingAs($this->userWithoutCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Tanpa Titik',
                'address' => 'Jl. Parangtritis Km 4, Bantul',
                'phone' => '089876543210',
                'latitude' => '-7.8014123',
                'longitude' => '110.3647891',
            ])
            ->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHas('success');

        $fresh = $this->customerWithoutCoords->fresh();
        $this->assertSame('-7.8014123', (string) $fresh->latitude);
        $this->assertSame('110.3647891', (string) $fresh->longitude);
    }

    // 4. Pembaruan dengan koordinat dikosongkan -> kedua kolom menjadi NULL (skenario Hapus Titik)
    public function test_clearing_coordinates_sets_both_columns_to_null(): void
    {
        $this->assertNotNull($this->customerWithCoords->latitude);
        $this->assertNotNull($this->customerWithCoords->longitude);

        $this->actingAs($this->userWithCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'phone' => '081234567890',
                'latitude' => '',
                'longitude' => '',
            ])
            ->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHas('success');

        $fresh = $this->customerWithCoords->fresh();
        $this->assertNull($fresh->latitude);
        $this->assertNull($fresh->longitude);
        $this->assertDatabaseHas('customers', [
            'id' => $this->customerWithCoords->id,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    // 5. Hanya latitude atau hanya longitude -> validasi gagal dan data lama tidak berubah
    public function test_only_latitude_or_only_longitude_is_rejected(): void
    {
        $this->actingAs($this->userWithCoords)
            ->from(route('nasabah.profil.edit'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => '-7.7828750',
                'longitude' => '',
            ])
            ->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHasErrors([
                'longitude' => 'Kolom longitude wajib diisi jika latitude diisi.',
            ]);

        $this->actingAs($this->userWithCoords)
            ->from(route('nasabah.profil.edit'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => '',
                'longitude' => '110.3670830',
            ])
            ->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude wajib diisi jika longitude diisi.',
            ]);

        $fresh = $this->customerWithCoords->fresh();
        $this->assertSame('-7.7654321', (string) $fresh->latitude);
        $this->assertSame('110.3789012', (string) $fresh->longitude);
    }

    // 6. Koordinat di luar rentang atau bukan angka -> validasi gagal dengan pesan Bahasa Indonesia
    public function test_out_of_range_or_non_numeric_coordinates_are_rejected(): void
    {
        $this->actingAs($this->userWithCoords)
            ->from(route('nasabah.profil.edit'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => '-91',
                'longitude' => '181',
            ])
            ->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude harus bernilai antara -90 sampai 90.',
                'longitude' => 'Kolom longitude harus bernilai antara -180 sampai 180.',
            ]);

        $this->actingAs($this->userWithCoords)
            ->from(route('nasabah.profil.edit'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => 'bukan-angka',
                'longitude' => '110.37abc',
            ])
            ->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude harus berupa angka.',
                'longitude' => 'Kolom longitude harus berupa angka.',
            ]);

        $fresh = $this->customerWithCoords->fresh();
        $this->assertSame('-7.7654321', (string) $fresh->latitude);
        $this->assertSame('110.3789012', (string) $fresh->longitude);
    }

    // 7. Setelah validasi gagal, nilai old koordinat tampil kembali menggantikan nilai tersimpan
    public function test_old_coordinates_override_saved_values_after_failed_validation(): void
    {
        // Skenario A: nasabah memilih titik baru lalu validasi alamat gagal -> titik baru tampil kembali
        $responseNew = $this->actingAs($this->userWithCoords)
            ->followingRedirects()
            ->from(route('nasabah.profil.edit'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => '',
                'phone' => '081234567890',
                'latitude' => '-7.8123456',
                'longitude' => '110.3543210',
            ]);

        $responseNew->assertOk()
            ->assertSee('Kolom alamat wajib diisi.')
            ->assertSee('value="-7.8123456"', false)
            ->assertSee('value="110.3543210"', false)
            ->assertDontSee('value="-7.7654321"', false);

        // Skenario B: nasabah menekan Hapus Titik (kosong) lalu validasi alamat gagal -> tetap kosong
        $responseCleared = $this->actingAs($this->userWithCoords)
            ->followingRedirects()
            ->from(route('nasabah.profil.edit'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => '',
                'latitude' => '',
                'longitude' => '',
            ]);

        $htmlCleared = $responseCleared->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*name="latitude"[^>]*readonly[^>]*value=""/s', $htmlCleared);
        $this->assertMatchesRegularExpression('/<input[^>]*name="longitude"[^>]*readonly[^>]*value=""/s', $htmlCleared);
    }

    // 8. Nasabah A tidak bisa mengubah koordinat nasabah B dengan mengirim identitas lain
    public function test_customer_a_cannot_modify_customer_b_coordinates_by_sending_foreign_ids(): void
    {
        $this->actingAs($this->userWithoutCoords)
            ->put(route('nasabah.profil.update'), [
                'id' => $this->customerWithCoords->id,
                'customer_id' => $this->customerWithCoords->id,
                'user_id' => $this->userWithCoords->id,
                'code' => $this->customerWithCoords->code,
                'name' => 'Nasabah Tanpa Titik',
                'address' => 'Jl. Parangtritis Km 4, Bantul',
                'latitude' => '-7.7900000',
                'longitude' => '110.3900000',
            ])
            ->assertRedirect(route('nasabah.profil.edit'));

        // Koordinat nasabah B (customerWithCoords) sama sekali tidak berubah
        $targetFresh = $this->customerWithCoords->fresh();
        $this->assertSame('-7.7654321', (string) $targetFresh->latitude);
        $this->assertSame('110.3789012', (string) $targetFresh->longitude);

        // Hanya koordinat milik pengirim sendiri (customerWithoutCoords) yang diperbarui
        $ownFresh = $this->customerWithoutCoords->fresh();
        $this->assertSame('-7.7900000', (string) $ownFresh->latitude);
        $this->assertSame('110.3900000', (string) $ownFresh->longitude);
    }
}
