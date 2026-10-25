<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature Test PBI-06b: Kelola Petugas oleh Admin.
 */
class OfficerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabah;

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
            'name' => 'Petugas Rian',
            'email' => 'rian@test.com',
            'phone' => '081234567890',
            'password' => 'password',
            'role' => 'petugas',
        ]);

        $this->nasabah = User::create([
            'name' => 'Nasabah Siti',
            'email' => 'siti@test.com',
            'password' => 'password',
            'role' => 'nasabah',
        ]);
        $this->nasabah->customer()->create([
            'name' => 'Nasabah Siti',
            'address' => 'Jl. Malioboro No. 5',
        ]);
    }

    public function test_admin_can_list_and_search_only_petugas_accounts(): void
    {
        User::create([
            'name' => 'Petugas Dedi',
            'email' => 'dedi@test.com',
            'phone' => '085600001111',
            'password' => 'password',
            'role' => 'petugas',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.petugas.index'))
            ->assertOk()
            ->assertSee('Petugas Rian')
            ->assertSee('Petugas Dedi')
            ->assertDontSee('admin@test.com')
            ->assertDontSee('siti@test.com');

        $this->actingAs($this->admin)
            ->get(route('admin.petugas.index', ['q' => 'Dedi']))
            ->assertOk()
            ->assertSee('Petugas Dedi')
            ->assertDontSee('Petugas Rian');
    }

    public function test_admin_can_create_update_and_reset_password_of_petugas(): void
    {
        // Tambah petugas baru (meski dikirim role=admin dari browser, tetap menjadi petugas)
        $response = $this->actingAs($this->admin)->post(route('admin.petugas.store'), [
            'name' => 'Petugas Baru',
            'email' => 'baru@test.com',
            'phone' => '081399998888',
            'password' => 'petugas123',
            'password_confirmation' => 'petugas123',
            'role' => 'admin',
        ]);

        $newOfficer = User::where('email', 'baru@test.com')->first();
        $this->assertNotNull($newOfficer);
        $this->assertSame('petugas', $newOfficer->role);
        $this->assertTrue($newOfficer->is_active);
        $this->assertSame('081399998888', $newOfficer->phone);
        $this->assertTrue(Hash::check('petugas123', $newOfficer->password));
        $response->assertRedirect(route('admin.petugas.show', $newOfficer));

        // Ubah nama, email, no HP (boleh mempertahankan email sendiri atau ganti email unik)
        $this->actingAs($this->admin)->put(route('admin.petugas.update', $newOfficer), [
            'name' => 'Petugas Baru Diubah',
            'email' => 'baru.ubah@test.com',
            'phone' => '081377776666',
            'role' => 'admin',
        ])->assertRedirect(route('admin.petugas.show', $newOfficer));

        $newOfficer->refresh();
        $this->assertSame('Petugas Baru Diubah', $newOfficer->name);
        $this->assertSame('baru.ubah@test.com', $newOfficer->email);
        $this->assertSame('081377776666', $newOfficer->phone);
        $this->assertSame('petugas', $newOfficer->role);

        // Reset password petugas
        $this->actingAs($this->admin)->patch(route('admin.petugas.reset-password', $newOfficer), [
            'password' => 'passwordbaru99',
            'password_confirmation' => 'passwordbaru99',
        ])->assertRedirect(route('admin.petugas.show', $newOfficer));

        $this->assertTrue(Hash::check('passwordbaru99', $newOfficer->fresh()->password));
    }

    public function test_email_validation_requires_unique_email_across_users(): void
    {
        // Email sudah dipakai oleh rian@test.com dan siti@test.com
        $this->actingAs($this->admin)->post(route('admin.petugas.store'), [
            'name' => 'Duplikat',
            'email' => 'rian@test.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($this->admin)->put(route('admin.petugas.update', $this->petugas), [
            'name' => 'Petugas Rian',
            'email' => 'siti@test.com',
            'phone' => '081234567890',
        ])->assertSessionHasErrors('email');

        // Menyimpan dengan email miliknya sendiri diperbolehkan
        $this->actingAs($this->admin)->put(route('admin.petugas.update', $this->petugas), [
            'name' => 'Petugas Rian Tetap',
            'email' => 'rian@test.com',
            'phone' => '081234567890',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_deactivate_is_idempotent_and_activate_restores_access(): void
    {
        // Nonaktifkan pertama kali
        $this->actingAs($this->admin)
            ->patch(route('admin.petugas.deactivate', $this->petugas))
            ->assertRedirect(route('admin.petugas.show', $this->petugas));
        $this->assertFalse($this->petugas->fresh()->is_active);

        // Nonaktifkan kedua kali -> tetap nonaktif (idempoten, tidak pernah toggle aktif kembali)
        $this->actingAs($this->admin)
            ->patch(route('admin.petugas.deactivate', $this->petugas))
            ->assertRedirect(route('admin.petugas.show', $this->petugas));
        $this->assertFalse($this->petugas->fresh()->is_active);

        // Aktifkan kembali
        $this->actingAs($this->admin)
            ->patch(route('admin.petugas.activate', $this->petugas))
            ->assertRedirect(route('admin.petugas.show', $this->petugas));
        $this->assertTrue($this->petugas->fresh()->is_active);
    }

    public function test_deactivated_officer_cannot_login(): void
    {
        $this->petugas->is_active = false;
        $this->petugas->save();

        $response = $this->post('/login', [
            'email' => 'rian@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => 'Akun dinonaktifkan. Hubungi admin.',
        ]);
    }

    public function test_active_session_of_deactivated_officer_is_logged_out_automatically(): void
    {
        $this->post('/login', [
            'email' => 'rian@test.com',
            'password' => 'password',
        ])->assertRedirect(route('petugas.dashboard'));
        $this->assertAuthenticatedAs($this->petugas);

        // Admin menonaktifkan akun petugas saat sesi masih aktif
        $this->petugas->is_active = false;
        $this->petugas->save();

        $response = $this->get(route('petugas.dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Akun dinonaktifkan. Hubungi admin.',
        ]);
    }

    public function test_inactive_officer_is_excluded_from_active_officers_selection(): void
    {
        $active2 = User::create([
            'name' => 'Petugas Aktif Dua',
            'email' => 'aktif2@test.com',
            'password' => 'password',
            'role' => 'petugas',
        ]);

        $this->petugas->is_active = false;
        $this->petugas->save();

        $selectableIds = User::activeOfficers()->pluck('id')->all();

        $this->assertContains($active2->id, $selectableIds);
        $this->assertNotContains($this->petugas->id, $selectableIds);
        $this->assertNotContains($this->admin->id, $selectableIds);
        $this->assertNotContains($this->nasabah->id, $selectableIds);
    }

    public function test_officer_menu_cannot_view_or_modify_admin_or_nasabah_accounts(): void
    {
        $otherAdmin = User::create([
            'name' => 'Admin Kedua',
            'email' => 'admin2@test.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        foreach ([$otherAdmin, $this->nasabah] as $nonOfficer) {
            $this->actingAs($this->admin)->get(route('admin.petugas.show', $nonOfficer))->assertForbidden();
            $this->actingAs($this->admin)->get(route('admin.petugas.edit', $nonOfficer))->assertForbidden();
            $this->actingAs($this->admin)->put(route('admin.petugas.update', $nonOfficer), [
                'name' => 'Diubah Paksa',
                'email' => 'paksa@test.com',
            ])->assertForbidden();
            $this->actingAs($this->admin)->patch(route('admin.petugas.reset-password', $nonOfficer), [
                'password' => 'paksa12345',
                'password_confirmation' => 'paksa12345',
            ])->assertForbidden();
            $this->actingAs($this->admin)->patch(route('admin.petugas.deactivate', $nonOfficer))->assertForbidden();
            $this->actingAs($this->admin)->patch(route('admin.petugas.activate', $nonOfficer))->assertForbidden();
        }

        $this->assertSame('Admin Kedua', $otherAdmin->fresh()->name);
        $this->assertTrue($otherAdmin->fresh()->is_active);
        $this->assertSame('Nasabah Siti', $this->nasabah->fresh()->name);
        $this->assertTrue($this->nasabah->fresh()->is_active);

        // Hard delete tidak tersedia
        $this->actingAs($this->admin)->delete("/admin/petugas/{$this->petugas->id}")->assertStatus(405);
    }

    public function test_petugas_and_nasabah_receive_403_on_officer_management_routes(): void
    {
        foreach ([$this->petugas, $this->nasabah] as $unauthorized) {
            $this->actingAs($unauthorized)->get(route('admin.petugas.index'))->assertForbidden();
            $this->actingAs($unauthorized)->get(route('admin.petugas.create'))->assertForbidden();
            $this->actingAs($unauthorized)->post(route('admin.petugas.store'), [
                'name' => 'Ilegal',
                'email' => 'ilegal@test.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])->assertForbidden();
            $this->actingAs($unauthorized)->get(route('admin.petugas.edit', $this->petugas))->assertForbidden();
            $this->actingAs($unauthorized)->put(route('admin.petugas.update', $this->petugas), [
                'name' => 'Ilegal',
                'email' => 'rian@test.com',
            ])->assertForbidden();
            $this->actingAs($unauthorized)->patch(route('admin.petugas.deactivate', $this->petugas))->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.petugas.index'))->assertRedirect(route('login'));
        $this->post(route('admin.petugas.store'), [])->assertRedirect(route('login'));
    }
}
