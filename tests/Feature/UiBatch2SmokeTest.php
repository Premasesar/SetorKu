<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\WasteType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiBatch2SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-b2@banksampah.test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createPetugas(): User
    {
        return User::create([
            'name' => 'Petugas Satu',
            'email' => 'petugas-b2@banksampah.test',
            'password' => 'password123',
            'role' => 'petugas',
            'is_active' => true,
        ]);
    }

    private function createNasabah(string $name = 'Budi Santoso', string $email = 'budi-b2@banksampah.test'): array
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
            'role' => 'nasabah',
            'is_active' => true,
        ]);

        $customer = $user->customer()->create([
            'name' => $name,
            'phone' => '081234567890',
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
            'latitude' => -7.7928000,
            'longitude' => 110.3658000,
        ]);
        $customer->balance = 25000;
        $customer->is_active = true;
        $customer->save();

        return [$user, $customer];
    }

    public function test_customers_index_uses_ui_staff_and_respects_admin_vs_petugas_actions(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        [$nasabah, $customer] = $this->createNasabah();

        $this->get(route('admin.nasabah.index'))->assertRedirect(route('login'));
        $this->get(route('petugas.nasabah.index'))->assertRedirect(route('login'));
        $this->actingAs($nasabah)->get(route('admin.nasabah.index'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.nasabah.index'))->assertForbidden();

        // Admin melihat tombol Tambah Nasabah, Detail, dan Ubah
        $adminResp = $this->actingAs($admin)->get(route('admin.nasabah.index'));
        $adminResp->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-staff', false)
            ->assertSee('sk-hero', false)
            ->assertSee('Data Nasabah')
            ->assertSee('Tambah Nasabah')
            ->assertSee('placeholder="Cari nama atau alamat..."', false)
            ->assertSee('Cari')
            ->assertSee($customer->code)
            ->assertSee($customer->name)
            ->assertSee('Rp 25.000')
            ->assertSee('Aktif')
            ->assertSee(route('admin.nasabah.show', $customer), false)
            ->assertSee(route('admin.nasabah.edit', $customer), false)
            ->assertSee('sk-mobile-list', false);

        // Petugas melihat tombol Tambah Nasabah & Detail, TANPA tautan Ubah
        $petugasResp = $this->actingAs($petugas)->get(route('petugas.nasabah.index'));
        $petugasResp->assertOk()
            ->assertSee('css/setorku-theme.css', false)
            ->assertSee('sk-staff', false)
            ->assertSee('Tambah Nasabah')
            ->assertSee(route('petugas.nasabah.show', $customer), false)
            ->assertDontSee(route('admin.nasabah.edit', $customer), false);
    }

    public function test_customers_create_and_edit_use_ui_staff_and_retain_all_form_fields(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        [$nasabah, $customer] = $this->createNasabah();

        $this->get(route('admin.nasabah.create'))->assertRedirect(route('login'));
        $this->actingAs($nasabah)->get(route('admin.nasabah.create'))->assertForbidden();
        $this->actingAs($petugas)->get(route('admin.nasabah.edit', $customer))->assertForbidden();

        // Create (Admin & Petugas)
        foreach ([
            [$admin, route('admin.nasabah.create')],
            [$petugas, route('petugas.nasabah.create')],
        ] as [$actor, $url]) {
            $this->actingAs($actor)->get($url)
                ->assertOk()
                ->assertSee('sk-staff', false)
                ->assertSee('Tambah Nasabah')
                ->assertSee('name="name"', false)
                ->assertSee('name="address"', false)
                ->assertSee('name="phone"', false)
                ->assertSee('name="latitude"', false)
                ->assertSee('name="longitude"', false)
                ->assertSee('name="email"', false)
                ->assertSee('name="password"', false)
                ->assertSee('name="password_confirmation"', false)
                ->assertSee('Simpan')
                ->assertSee('Batal');
        }

        // Edit (Admin only)
        $this->actingAs($admin)->get(route('admin.nasabah.edit', $customer))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Ubah Data Nasabah')
            ->assertSee('name="name"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('name="email"', false)
            ->assertDontSee('name="password"', false)
            ->assertSee('Simpan Perubahan')
            ->assertSee('Batal');
    }

    public function test_customers_show_uses_ui_staff_and_respects_role_actions(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        [$nasabah, $customer] = $this->createNasabah();

        $this->get(route('admin.nasabah.show', $customer))->assertRedirect(route('login'));
        $this->actingAs($nasabah)->get(route('admin.nasabah.show', $customer))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.nasabah.show', $customer))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($customer->code)
            ->assertSee($customer->name)
            ->assertSee('Rp 25.000')
            ->assertSee('Cetak kartu')
            ->assertSee('Ubah')
            ->assertSee('Nonaktifkan')
            ->assertSee('Kembali ke daftar');

        $this->actingAs($petugas)->get(route('petugas.nasabah.show', $customer))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee($customer->code)
            ->assertSee('Cetak kartu')
            ->assertDontSee(route('admin.nasabah.edit', $customer), false)
            ->assertDontSee(route('admin.nasabah.deactivate', $customer), false);
    }

    public function test_customers_card_uses_dynamic_layout_for_staff_and_nasabah(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        [$nasabah, $customer] = $this->createNasabah();

        // Admin -> layouts.ui-staff
        $this->actingAs($admin)->get(route('admin.nasabah.card', $customer))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Cetak Kartu')
            ->assertSee('id="customer-qr-code"', false)
            ->assertSee('data-qr-payload="' . $customer->code . '"', false)
            ->assertSee('@media print', false)
            ->assertDontSee('25.000');

        // Petugas -> layouts.ui-staff
        $this->actingAs($petugas)->get(route('petugas.nasabah.card', $customer))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Cetak Kartu')
            ->assertSee('id="customer-qr-code"', false)
            ->assertDontSee('25.000');

        // Nasabah -> layouts.ui-nasabah
        $this->actingAs($nasabah)->get(route('nasabah.kartu.show'))
            ->assertOk()
            ->assertSee('sk-nasabah-header', false)
            ->assertDontSee('<aside class="sk-sidebar"', false)
            ->assertSee('Kartu Saya')
            ->assertSee('Cetak Kartu')
            ->assertSee('id="customer-qr-code"', false)
            ->assertSee('data-qr-payload="' . $customer->code . '"', false)
            ->assertSee('@media print', false)
            ->assertDontSee('25.000');
    }

    public function test_waste_types_views_use_ui_staff_and_enforce_role_permissions(): void
    {
        $admin = $this->createAdmin();
        $petugas = $this->createPetugas();
        [$nasabah] = $this->createNasabah();

        $activeWt = WasteType::create([
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 3500,
        ]);
        $inactiveWt = WasteType::create([
            'name' => 'Kaca Bening',
            'unit' => 'kg',
            'price_per_unit' => 1200,
        ]);
        $inactiveWt->is_active = false;
        $inactiveWt->save();

        // Index Admin
        $this->actingAs($admin)->get(route('admin.jenis-sampah.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Jenis & Harga Sampah', false)
            ->assertSee('Tambah Jenis Sampah')
            ->assertSee('placeholder="Cari nama jenis sampah..."', false)
            ->assertSee('Plastik PET')
            ->assertSee('Kaca Bening')
            ->assertSee('Nonaktifkan')
            ->assertSee('Aktifkan')
            ->assertSee('sk-mobile-list', false);

        // Index Petugas (hanya lihat aktif, tanpa tombol Tambah/Ubah/Nonaktifkan)
        $this->actingAs($petugas)->get(route('petugas.jenis-sampah.index'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Plastik PET')
            ->assertDontSee('Kaca Bening')
            ->assertDontSee('Tambah Jenis Sampah')
            ->assertDontSee('Nonaktifkan');

        // Create Admin
        $this->actingAs($admin)->get(route('admin.jenis-sampah.create'))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Tambah Jenis Sampah')
            ->assertSee('name="name"', false)
            ->assertSee('name="unit"', false)
            ->assertSee('name="price_per_unit"', false)
            ->assertSee('Simpan')
            ->assertSee('Batal');

        // Edit Admin
        $this->actingAs($admin)->get(route('admin.jenis-sampah.edit', $activeWt))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Ubah Jenis Sampah')
            ->assertSee('Plastik PET')
            ->assertSee('Simpan Perubahan')
            ->assertSee('Batal');

        // Show Admin
        $this->actingAs($admin)->get(route('admin.jenis-sampah.show', $activeWt))
            ->assertOk()
            ->assertSee('sk-staff', false)
            ->assertSee('Plastik PET')
            ->assertSee('Rp 3.500,00')
            ->assertSee('Aktif')
            ->assertSee('Kembali ke daftar');

        // Nasabah & Petugas ditolak dari halaman CRUD Admin
        $this->actingAs($petugas)->get(route('admin.jenis-sampah.show', $activeWt))->assertForbidden();
        $this->actingAs($nasabah)->get(route('admin.jenis-sampah.index'))->assertForbidden();
        $this->actingAs($nasabah)->get(route('petugas.jenis-sampah.index'))->assertForbidden();
    }
}
