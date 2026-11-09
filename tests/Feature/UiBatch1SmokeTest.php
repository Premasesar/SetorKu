<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Services\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteTwoUser;
use Mockery;
use Tests\TestCase;

class UiBatch1SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-b1@banksampah.test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createPetugas(string $suffix = '1'): User
    {
        return User::create([
            'name' => 'Petugas ' . $suffix,
            'email' => "petugas-b1-{$suffix}@banksampah.test",
            'password' => 'password123',
            'role' => 'petugas',
            'is_active' => true,
        ]);
    }

    private function createNasabah(): array
    {
        $user = User::create([
            'name' => 'Siti Aminah',
            'email' => 'nasabah-b1@banksampah.test',
            'password' => 'password123',
            'role' => 'nasabah',
            'is_active' => true,
        ]);

        $customer = $user->customer()->create([
            'name' => $user->name,
            'phone' => '081234567899',
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
            'latitude' => -7.7928000,
            'longitude' => 110.3658000,
        ]);

        return [$user, $customer];
    }

    private function createScheduledPickup(Customer $customer, User $officer, User $admin, ?int $sequence = 1): PickupRequest
    {
        $service = app(PickupService::class);
        $pickup = $service->submitRequest($customer, [
            'requested_date' => now()->toDateString(),
            'address' => $customer->address,
            'latitude' => -7.7928000,
            'longitude' => 110.3658000,
            'notes' => 'Titip di teras depan',
        ]);

        $scheduled = $service->schedule(
            pickupRequest: $pickup,
            scheduledDate: now()->toDateString(),
            officerId: $officer->id,
            admin: $admin,
        );

        $scheduled->route_sequence = $sequence;
        $scheduled->save();

        return $scheduled;
    }

    public function test_google_complete_registration_uses_ui_guest_and_retains_all_elements(): void
    {
        $this->get(route('google.register.form'))->assertRedirect(route('login'));

        $socialiteUser = SocialiteTwoUser::fake([
            'id' => 'gid-ui-b1-001',
            'name' => 'Calon Nasabah Baru',
            'email' => 'calon.baru@gmail.com',
            'email_verified' => true,
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get(route('google.callback'))->assertRedirect(route('google.register.form'));

        $response = $this->get(route('google.register.form'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-guest', false)
            ->assertSee('sk-guest__card--wide', false)
            ->assertSee('Lengkapi Pendaftaran')
            ->assertSee('id="google_email_display"', false)
            ->assertSee('calon.baru@gmail.com')
            ->assertSee('name="name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('data-map-picker', false)
            ->assertSee('Gunakan Lokasi Saya')
            ->assertSee('Kembalikan Titik Semula')
            ->assertSee('Hapus Titik')
            ->assertSee('Selesaikan Pendaftaran')
            ->assertSee('Kembali ke halaman login');
    }

    public function test_petugas_dashboard_uses_ui_staff_and_enforces_role_access(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas('1');
        [$nasabah, $customer] = $this->createNasabah();
        $pickup = $this->createScheduledPickup($customer, $petugas, $admin, 1);

        $this->get(route('petugas.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('petugas.dashboard'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.dashboard'))->assertForbidden();

        $response = $this->actingAs($petugas)->get(route('petugas.dashboard'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Dashboard Petugas')
            ->assertSee('Petugas')
            ->assertSee('Catat Setoran')
            ->assertSee('Catat Penarikan')
            ->assertSee('Tugas Penjemputan Hari Ini')
            ->assertSee('Setoran Dicatat Hari Ini')
            ->assertSee('Penarikan Dicatat Hari Ini')
            ->assertSee('Semua Tugas Terjadwal')
            ->assertSee($pickup->code)
            ->assertSee('Detail & Catat Hasil', false);
    }

    public function test_petugas_pickup_index_and_route_map_partial_use_ui_staff_and_retain_elements(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas('1');
        [$nasabah, $customer] = $this->createNasabah();
        $pickup = $this->createScheduledPickup($customer, $petugas, $admin, 1);

        $this->get(route('petugas.penjemputan.index'))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('petugas.penjemputan.index'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.penjemputan.index'))->assertForbidden();

        $response = $this->actingAs($petugas)->get(route('petugas.penjemputan.index'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Tugas Penjemputan Terjadwal')
            ->assertSee('Halaman Peta Rute Saya')
            ->assertSee('Peta Rute Penjemputan Saya')
            ->assertSee('id="officer-route-map"', false)
            ->assertSee('Urutan')
            ->assertSee('Kode')
            ->assertSee('Tgl Jadwal')
            ->assertSee('Nasabah')
            ->assertSee('Alamat')
            ->assertSee('Catatan')
            ->assertSee($pickup->code)
            ->assertSee('Detail & Catat Hasil', false)
            ->assertSee('sk-mobile-list', false);
    }

    public function test_petugas_pickup_show_uses_ui_staff_and_retains_map_and_dynamic_form(): void
    {
        $admin = $this->createAdmin();
        $petugas1 = $this->createPetugas('1');
        $petugas2 = $this->createPetugas('2');
        [$nasabah, $customer] = $this->createNasabah();
        $pickup = $this->createScheduledPickup($customer, $petugas1, $admin, 1);

        $wasteType = WasteType::create([
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 3500,
            'is_active' => true,
        ]);

        $this->get(route('petugas.penjemputan.show', $pickup))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('petugas.penjemputan.show', $pickup))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.penjemputan.show', $pickup))->assertForbidden();
        $this->actingAs($petugas2)->get(route('petugas.penjemputan.show', $pickup))->assertForbidden();

        $response = $this->actingAs($petugas1)->get(route('petugas.penjemputan.show', $pickup));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Informasi Nasabah & Lokasi', false)
            ->assertSee('Peta & Navigasi', false)
            ->assertSee('Google Maps')
            ->assertSee('OpenStreetMap')
            ->assertSee('id="officer-pickup-map"', false)
            ->assertSee('Catat Hasil Timbangan Penjemputan')
            ->assertSee('id="btn-add-row"', false)
            ->assertSee('id="items-container"', false)
            ->assertSee('name="items[0][waste_type_id]"', false)
            ->assertSee('name="items[0][weight]"', false)
            ->assertSee($wasteType->name)
            ->assertSee('Simpan Setoran & Selesaikan Penjemputan', false)
            ->assertSee('Kembali ke daftar tugas');

        // Saat penjemputan sudah selesai, tampilkan tabel/kartu Bukti Setoran Hasil Penjemputan
        $deposits = app(PickupService::class)->completePickup(
            pickupRequest: $pickup,
            items: [['waste_type_id' => $wasteType->id, 'weight' => 2.500]],
            officer: $petugas1,
        );

        $completedResponse = $this->actingAs($petugas1)->get(route('petugas.penjemputan.show', $pickup->fresh()));
        $completedResponse->assertOk()
            ->assertSee('Bukti Setoran Hasil Penjemputan')
            ->assertSee($deposits->first()->code)
            ->assertSee('sk-mobile-list', false);
    }

    public function test_petugas_dedicated_route_page_uses_ui_staff_and_enforces_role_access(): void
    {
        $admin = $this->createAdmin();
        $petugas1 = $this->createPetugas('1');
        $petugas2 = $this->createPetugas('2');
        [$nasabah, $customer] = $this->createNasabah();
        $pickup = $this->createScheduledPickup($customer, $petugas1, $admin, 1);

        $this->get(route('petugas.rute.show'))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('petugas.rute.show'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.rute.show'))->assertForbidden();
        $this->actingAs($petugas2)->get(route('petugas.rute.show', ['officer' => $petugas1->id]))->assertForbidden();

        $response = $this->actingAs($petugas1)->get(route('petugas.rute.show'));

        $response->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Rute Penjemputan Saya (Read-Only)')
            ->assertSee('Kembali ke Tugas Jemput')
            ->assertSee('Peta Rute Penjemputan Saya')
            ->assertSee('id="officer-route-map"', false)
            ->assertSee('Daftar Titik pada Tanggal')
            ->assertSee($pickup->code)
            ->assertSee('Detail & Catat Hasil', false)
            ->assertSee('sk-mobile-list', false);
    }
}
