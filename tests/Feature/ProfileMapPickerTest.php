<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-17 & PBI-18:
 * - Pemisahan Halaman Lihat Profil (/nasabah/profil) dan Halaman Ubah Profil (/nasabah/profil/ubah)
 * - Peta mode baca vs mode ubah beserta atribut pengaman titik (<x-map-picker>)
 * - Validasi koordinat, pengosongan NULL, isolasi peran, dan akun nonaktif.
 */
class ProfileMapPickerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $userWithCoords;
    private Customer $customerWithCoords;
    private User $userWithoutCoords;
    private Customer $customerWithoutCoords;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Sistem',
            'email' => 'admin.pbi18@test.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->petugas = User::create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas.pbi18@test.com',
            'password' => 'password',
            'role' => 'petugas',
        ]);

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
            'phone' => null,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    // 1. GET /nasabah/profil menampilkan data akun & data diri dalam mode baca, tanpa form input, dengan tombol "Ubah Profil"
    public function test_profile_view_page_renders_read_only_details_without_form_inputs(): void
    {
        $response = $this->actingAs($this->userWithoutCoords)->get(route('nasabah.profil.edit'));

        $response->assertOk()
            ->assertSee('Informasi Akun')
            ->assertSee('Data Diri')
            ->assertSee($this->customerWithoutCoords->code)
            ->assertSee('tanpatitik@test.com')
            ->assertSee('Rp 0,00')
            ->assertSee('Nasabah Tanpa Titik')
            ->assertSee('Jl. Parangtritis Km 4, Bantul')
            ->assertSee('<dd class="col-sm-8">-</dd>', false) // No. HP kosong tampil "-"
            ->assertSee('Ubah Profil')
            ->assertSee(route('nasabah.profil.ubah'), false)
            ->assertSee('Kembali')
            ->assertDontSee('name="name"', false)
            ->assertDontSee('name="address"', false)
            ->assertDontSee('name="phone"', false)
            ->assertDontSee('name="latitude"', false)
            ->assertDontSee('name="longitude"', false)
            ->assertDontSee('Simpan Perubahan');
    }

    // 2. Halaman Lihat: nasabah dengan titik memuat peta mode baca; nasabah tanpa titik tampil info tanpa kontainer peta
    public function test_profile_view_page_renders_readonly_map_only_when_coordinates_exist(): void
    {
        // Nasabah DENGAN titik koordinat -> peta mode baca tampil, tanpa input & tanpa tombol ubah/geolokasi/hapus
        $withCoordsResp = $this->actingAs($this->userWithCoords)->get(route('nasabah.profil.edit'));
        $withCoordsResp->assertOk()
            ->assertSee('-7.7654321, 110.3789012')
            ->assertSee('data-map-picker-mode="view"', false)
            ->assertSee('data-init-lat="-7.7654321"', false)
            ->assertSee('data-init-lng="110.3789012"', false)
            ->assertSee('data-selected-zoom="17"', false)
            ->assertSee('data-map-picker-canvas', false)
            ->assertDontSee('Titik lokasi rumah belum ditentukan.')
            ->assertDontSee('data-map-picker-locate', false)
            ->assertDontSee('data-map-picker-clear', false)
            ->assertDontSee('data-map-picker-unlock', false)
            ->assertDontSee('name="latitude"', false);

        $htmlWithMap = $withCoordsResp->getContent();
        $this->assertSame(1, substr_count($htmlWithMap, 'leaflet@1.9.4/dist/leaflet.js'));

        // Nasabah TANPA titik koordinat -> kotak informasi tampil, TIDAK ada kontainer peta maupun skrip Leaflet
        $withoutCoordsResp = $this->actingAs($this->userWithoutCoords)->get(route('nasabah.profil.edit'));
        $withoutCoordsResp->assertOk()
            ->assertSee('Titik lokasi rumah belum ditentukan.')
            ->assertDontSee('data-map-picker', false)
            ->assertDontSee('data-map-picker-canvas', false)
            ->assertDontSee('leaflet@1.9.4/dist/leaflet.js', false);
    }

    // 3. GET /nasabah/profil/ubah menampilkan form terisi data tersimpan, peta mode ubah, pengaman titik, dan sidebar tetap aktif
    public function test_profile_edit_page_renders_prefilled_form_and_edit_map_picker_with_safeguards(): void
    {
        // Nasabah yang SUDAH punya koordinat -> awal TERKUNCI (tombol Ubah Titik tampil)
        $respSaved = $this->actingAs($this->userWithCoords)->get(route('nasabah.profil.ubah'));
        $respSaved->assertOk()
            ->assertSee('Ubah Profil Saya')
            ->assertSee('Titik Lokasi Rumah (opsional)')
            ->assertSee('Klik pada peta atau geser pin ke lokasi rumah Anda.')
            ->assertSee('data-map-picker-mode="edit"', false)
            ->assertSee('Titik terkunci agar tidak berpindah tanpa sengaja.')
            ->assertSee('data-map-picker-unlock', false)
            ->assertSee('Ubah Titik')
            ->assertSee('data-map-picker-locate', false)
            ->assertSee('Gunakan Lokasi Saya')
            ->assertSee('data-map-picker-reset', false)
            ->assertSee('Kembalikan Titik Semula')
            ->assertSee('data-map-picker-clear', false)
            ->assertSee('Hapus Titik')
            ->assertSee('Simpan Perubahan')
            ->assertSee('Batal');

        $htmlSaved = $respSaved->getContent();

        // Peta berada di antara kolom Alamat dan kolom Latitude/Longitude
        $addressPos = strpos($htmlSaved, 'id="address"');
        $mapPos = strpos($htmlSaved, 'data-map-picker-canvas');
        $latPos = strpos($htmlSaved, 'name="latitude"');
        $this->assertNotFalse($addressPos);
        $this->assertTrue($addressPos < $mapPos && $mapPos < $latPos);

        // Nilai tersimpan dirender sebagai nilai awal pada input readonly
        $this->assertMatchesRegularExpression('/<input[^>]*name="name"[^>]*value="Nasabah Koordinat"/s', $htmlSaved);
        $this->assertMatchesRegularExpression('/<input[^>]*name="phone"[^>]*value="081234567890"/s', $htmlSaved);
        $this->assertStringContainsString('Jl. Kaliurang Km 5, Sleman</textarea>', $htmlSaved);
        $this->assertMatchesRegularExpression('/<input[^>]*name="latitude"[^>]*readonly[^>]*value="-7\.7654321"/s', $htmlSaved);
        $this->assertMatchesRegularExpression('/<input[^>]*name="longitude"[^>]*readonly[^>]*value="110\.3789012"/s', $htmlSaved);

        // Menu "Profil Saya" di sidebar tetap berstatus active pada halaman ubah
        $this->assertMatchesRegularExpression(
            '/<a\s+class="nav-link\s+active"\s+href="' . preg_quote(route('nasabah.profil.edit'), '/') . '">/s',
            $htmlSaved
        );

        // Leaflet CSS, JS, dan skrip komponen dimuat tepat sekali
        $this->assertSame(1, substr_count($htmlSaved, 'leaflet@1.9.4/dist/leaflet.css'));
        $this->assertSame(1, substr_count($htmlSaved, 'leaflet@1.9.4/dist/leaflet.js'));
        $this->assertSame(1, substr_count($htmlSaved, 'data-setorku-map-picker-script'));

        // Nasabah TANPA koordinat -> bingkai aktif hijau & status awal mode ubah aktif
        $respEmpty = $this->actingAs($this->userWithoutCoords)->get(route('nasabah.profil.ubah'));
        $respEmpty->assertOk()
            ->assertSee('map-picker-canvas--active', false)
            ->assertSee('Mode ubah titik aktif: klik peta atau geser pin.');
    }

    // 4. Update valid -> redirect ke /nasabah/profil dengan flash "Profil berhasil diperbarui." dan tersimpan di database
    public function test_valid_update_redirects_to_view_page_with_flash_and_persists_data(): void
    {
        $putResponse = $this->actingAs($this->userWithoutCoords)
            ->from(route('nasabah.profil.ubah'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Baru Tersimpan',
                'address' => 'Jl. Malioboro No. 50, Yogyakarta',
                'phone' => '081122334455',
                'latitude' => '-7.8014123',
                'longitude' => '110.3647891',
            ]);

        $putResponse->assertRedirect(route('nasabah.profil.edit'))
            ->assertSessionHas('success', 'Profil berhasil diperbarui.');

        $fresh = $this->customerWithoutCoords->fresh();
        $this->assertSame('Nasabah Baru Tersimpan', $fresh->name);
        $this->assertSame('Nasabah Baru Tersimpan', $this->userWithoutCoords->fresh()->name);
        $this->assertSame('Jl. Malioboro No. 50, Yogyakarta', $fresh->address);
        $this->assertSame('081122334455', $fresh->phone);
        $this->assertSame('-7.8014123', (string) $fresh->latitude);
        $this->assertSame('110.3647891', (string) $fresh->longitude);

        // Ikuti redirect ke halaman LIHAT -> alert sukses tampil (role="alert") beserta data baru tanpa input form
        $viewResponse = $this->actingAs($this->userWithoutCoords)
            ->get(route('nasabah.profil.edit'));
        $viewResponse->assertOk()
            ->assertSee('Nasabah Baru Tersimpan')
            ->assertSee('-7.8014123, 110.3647891')
            ->assertSee('data-map-picker-mode="view"', false)
            ->assertDontSee('name="latitude"', false);
    }

    // 5. Update tidak valid -> kembali ke /nasabah/profil/ubah dengan ringkasan error dan old input
    public function test_invalid_update_redirects_back_to_edit_page_with_errors_and_old_inputs(): void
    {
        // Bahkan tanpa header Referer (->from()), $redirectRoute memastikan kembali ke /nasabah/profil/ubah
        $putResponse = $this->actingAs($this->userWithCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nama Baru Belum Tersimpan',
                'address' => '',
                'phone' => '081299998888',
                'latitude' => '-7.8123456',
                'longitude' => '110.3543210',
            ]);

        $putResponse->assertRedirect(route('nasabah.profil.ubah'))
            ->assertSessionHasErrors(['address' => 'Kolom alamat wajib diisi.']);

        $followed = $this->actingAs($this->userWithCoords)
            ->followingRedirects()
            ->from(route('nasabah.profil.ubah'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nama Baru Belum Tersimpan',
                'address' => '',
                'phone' => '081299998888',
                'latitude' => '-7.8123456',
                'longitude' => '110.3543210',
            ]);

        $followed->assertOk()
            ->assertSee('Ubah Profil Saya')
            ->assertSee('Profil gagal disimpan. Periksa kembali isian berikut:')
            ->assertSee('Kolom alamat wajib diisi.')
            ->assertSee('value="Nama Baru Belum Tersimpan"', false)
            ->assertSee('value="081299998888"', false)
            ->assertSee('value="-7.8123456"', false)
            ->assertSee('value="110.3543210"', false)
            ->assertDontSee('value="-7.7654321"', false);

        // Skenario Hapus Titik lalu validasi gagal -> nilai old kosong tetap dipertahankan
        $clearedFollowed = $this->actingAs($this->userWithCoords)
            ->followingRedirects()
            ->from(route('nasabah.profil.ubah'))
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => '',
                'latitude' => '',
                'longitude' => '',
            ]);

        $htmlCleared = $clearedFollowed->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*name="latitude"[^>]*readonly[^>]*value=""/s', $htmlCleared);
        $this->assertMatchesRegularExpression('/<input[^>]*name="longitude"[^>]*readonly[^>]*value=""/s', $htmlCleared);
    }

    // 6a. Koordinat dikosongkan -> kedua kolom menjadi NULL di database (skenario Hapus Titik)
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
            ->assertSessionHas('success', 'Profil berhasil diperbarui.');

        $fresh = $this->customerWithCoords->fresh();
        $this->assertNull($fresh->latitude);
        $this->assertNull($fresh->longitude);
        $this->assertDatabaseHas('customers', [
            'id' => $this->customerWithCoords->id,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    // 6b. Hanya salah satu koordinat, di luar rentang, atau bukan angka -> validasi gagal & data lama utuh
    public function test_invalid_coordinates_fail_validation_and_preserve_saved_coordinates(): void
    {
        // Hanya salah satu koordinat
        $this->actingAs($this->userWithCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => '-7.7828750',
                'longitude' => '',
            ])
            ->assertRedirect(route('nasabah.profil.ubah'))
            ->assertSessionHasErrors(['longitude' => 'Kolom longitude wajib diisi jika latitude diisi.']);

        $this->actingAs($this->userWithCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => '',
                'longitude' => '110.3670830',
            ])
            ->assertRedirect(route('nasabah.profil.ubah'))
            ->assertSessionHasErrors(['latitude' => 'Kolom latitude wajib diisi jika longitude diisi.']);

        // Di luar rentang (-90..90 / -180..180)
        $this->actingAs($this->userWithCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => '-91',
                'longitude' => '181',
            ])
            ->assertRedirect(route('nasabah.profil.ubah'))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude harus bernilai antara -90 sampai 90.',
                'longitude' => 'Kolom longitude harus bernilai antara -180 sampai 180.',
            ]);

        // Bukan angka
        $this->actingAs($this->userWithCoords)
            ->put(route('nasabah.profil.update'), [
                'name' => 'Nasabah Koordinat',
                'address' => 'Jl. Kaliurang Km 5, Sleman',
                'latitude' => 'bukan-angka',
                'longitude' => '110.37abc',
            ])
            ->assertRedirect(route('nasabah.profil.ubah'))
            ->assertSessionHasErrors([
                'latitude' => 'Kolom latitude harus berupa angka.',
                'longitude' => 'Kolom longitude harus berupa angka.',
            ]);

        $fresh = $this->customerWithCoords->fresh();
        $this->assertSame('-7.7654321', (string) $fresh->latitude);
        $this->assertSame('110.3789012', (string) $fresh->longitude);
    }

    // 7. Kontrol akses: tamu, Admin/Petugas, isolasi antar-nasabah, dan nasabah nonaktif
    public function test_access_control_on_profile_view_edit_and_update_routes(): void
    {
        // Tamu diarahkan ke login
        $this->get(route('nasabah.profil.edit'))->assertRedirect(route('login'));
        $this->get(route('nasabah.profil.ubah'))->assertRedirect(route('login'));
        $this->put(route('nasabah.profil.update'), ['name' => 'X', 'address' => 'Y'])->assertRedirect(route('login'));

        // Admin dan Petugas mendapat 403 di halaman lihat, halaman ubah, maupun endpoint simpan
        $this->actingAs($this->admin)->get(route('nasabah.profil.edit'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('nasabah.profil.ubah'))->assertForbidden();
        $this->actingAs($this->admin)->put(route('nasabah.profil.update'), ['name' => 'X', 'address' => 'Y'])->assertForbidden();

        $this->actingAs($this->petugas)->get(route('nasabah.profil.edit'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('nasabah.profil.ubah'))->assertForbidden();
        $this->actingAs($this->petugas)->put(route('nasabah.profil.update'), ['name' => 'X', 'address' => 'Y'])->assertForbidden();

        // Nasabah A tidak bisa mengubah data/koordinat Nasabah B dengan menyisipkan ID asing
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

        $this->assertSame('-7.7654321', (string) $this->customerWithCoords->fresh()->latitude);
        $this->assertSame('110.3789012', (string) $this->customerWithCoords->fresh()->longitude);
        $this->assertSame('-7.7900000', (string) $this->customerWithoutCoords->fresh()->latitude);
        $this->assertSame('110.3900000', (string) $this->customerWithoutCoords->fresh()->longitude);

        // Nasabah nonaktif ditolak otomatis dan di-logout oleh middleware EnsureAccountIsActive
        $this->customerWithCoords->is_active = false;
        $this->customerWithCoords->save();

        $this->actingAs($this->userWithCoords->fresh())
            ->get(route('nasabah.profil.ubah'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
