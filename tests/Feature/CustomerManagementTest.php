<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature Test PBI-02: Pengelolaan Data Nasabah (hak akses Admin).
 *
 * Cakupan:
 * - Akses Admin ke daftar, tambah, detail, ubah, nonaktifkan
 * - Penolakan akses Petugas, Nasabah (403) dan guest (redirect login)
 * - Validasi input (wajib, email unik, password, koordinat)
 * - Akun User berperan Nasabah terbuat dengan password ter-hash
 * - Saldo tidak dapat diisi/diubah dari form
 */
class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabahUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->petugas = User::create([
            'name' => 'Petugas', 'email' => 'petugas@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->nasabahUser = User::create([
            'name' => 'Nasabah', 'email' => 'nasabah@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
    }

    private function makeCustomer(string $nama = 'Siti Aminah', string $email = 'siti@test.com', string $alamat = 'Jl. Mawar No. 12'): Customer
    {
        $user = User::create(['name' => $nama, 'email' => $email, 'password' => 'password', 'role' => 'nasabah']);

        return $user->customer()->create(['name' => $nama, 'address' => $alamat]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'address' => 'Jl. Melati No. 5',
            'latitude' => '-6.2000000',
            'longitude' => '106.8166667',
            'email' => 'budi@test.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    // ─── Akses Admin ────────────────────────────────────────────

    public function test_admin_can_view_customer_list(): void
    {
        $this->makeCustomer();

        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.index'))
            ->assertOk()
            ->assertSee('Data Nasabah')
            ->assertSee('Siti Aminah')
            ->assertSee('siti@test.com');
    }

    public function test_customer_list_shows_empty_state(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.index'))
            ->assertOk()
            ->assertSee('Belum ada data nasabah.');
    }

    public function test_customer_list_can_be_searched(): void
    {
        $this->makeCustomer('Siti Aminah', 'siti@test.com');
        $this->makeCustomer('Joko Prasetyo', 'joko@test.com', 'Jl. Kenanga No. 3');

        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.index', ['q' => 'Joko']))
            ->assertOk()
            ->assertSee('Joko Prasetyo')
            ->assertDontSee('Siti Aminah');

        // Pencarian berdasarkan email akun
        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.index', ['q' => 'Siti']))
            ->assertSee('Siti Aminah')
            ->assertDontSee('Joko Prasetyo');
    }

    public function test_customer_search_without_result_shows_message(): void
    {
        $this->makeCustomer();

        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.index', ['q' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada nasabah yang cocok');
    }

    public function test_customer_list_is_paginated(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeCustomer("Nasabah {$i}", "n{$i}@test.com");
        }

        $response = $this->actingAs($this->admin)->get(route('admin.nasabah.index'));

        $response->assertOk();
        $customers = $response->viewData('customers');
        $this->assertCount(10, $customers);
        $this->assertSame(12, $customers->total());

        $page2 = $this->actingAs($this->admin)->get(route('admin.nasabah.index', ['page' => 2]));
        $this->assertCount(2, $page2->viewData('customers'));
    }

    public function test_admin_can_view_create_form_without_saldo_field(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.create'))
            ->assertOk()
            ->assertSee('Tambah Nasabah')
            ->assertDontSee('name="balance"', false);
    }

    public function test_admin_can_create_customer_and_user_account_is_created(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload());

        $user = User::where('email', 'budi@test.com')->first();
        $this->assertNotNull($user, 'Akun User nasabah harus terbuat.');
        $this->assertSame('nasabah', $user->role);
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertNotSame('rahasia123', $user->password, 'Password tidak boleh disimpan plain text.');
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        $customer = $user->customer;
        $this->assertNotNull($customer);
        $this->assertSame('Budi Santoso', $customer->name);
        $this->assertSame('Jl. Melati No. 5', $customer->address);
        $this->assertEquals(0, (float) $customer->balance);
        $this->assertTrue($customer->is_active);

        $response->assertRedirect(route('admin.nasabah.show', $customer));
        $response->assertSessionHas('success');
    }

    public function test_created_customer_can_login_as_nasabah(): void
    {
        $this->actingAs($this->admin)->post(route('admin.nasabah.store'), $this->validPayload());
        auth()->logout();

        $this->post('/login', ['email' => 'budi@test.com', 'password' => 'rahasia123'])
            ->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_coordinates_are_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['latitude' => '', 'longitude' => '']))
            ->assertSessionHasNoErrors();

        $customer = Customer::first();
        $this->assertNull($customer->latitude);
        $this->assertNull($customer->longitude);
    }

    public function test_saldo_input_is_ignored_on_create(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['balance' => 999999]));

        $this->assertEquals(0, (float) Customer::first()->balance);
    }

    public function test_admin_can_view_customer_detail(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.show', $customer))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('siti@test.com')
            ->assertSee('Rp 0');
    }

    public function test_admin_can_view_edit_form(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.edit', $customer))
            ->assertOk()
            ->assertSee('Ubah Data Nasabah')
            ->assertSee('Siti Aminah')
            ->assertDontSee('name="balance"', false);
    }

    public function test_admin_can_update_customer_and_user_is_synced(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($this->admin)->put(route('admin.nasabah.update', $customer), [
            'name' => 'Siti Aminah Baru',
            'address' => 'Jl. Anggrek No. 7',
            'latitude' => '-6.1',
            'longitude' => '106.8',
            'email' => 'siti.baru@test.com',
        ]);

        $response->assertRedirect(route('admin.nasabah.show', $customer));
        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertSame('Siti Aminah Baru', $customer->name);
        $this->assertSame('Jl. Anggrek No. 7', $customer->address);
        $this->assertSame('Siti Aminah Baru', $customer->user->name);
        $this->assertSame('siti.baru@test.com', $customer->user->email);
        $this->assertSame('nasabah', $customer->user->role);
    }

    public function test_saldo_cannot_be_changed_on_update(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)->put(route('admin.nasabah.update', $customer), [
            'name' => 'Siti Aminah',
            'address' => 'Jl. Mawar No. 12',
            'email' => 'siti@test.com',
            'balance' => 500000,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(0, (float) $customer->fresh()->balance);
    }

    public function test_update_allows_keeping_own_email(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)->put(route('admin.nasabah.update', $customer), [
            'name' => 'Siti',
            'address' => 'Jl. Mawar',
            'email' => 'siti@test.com',
        ])->assertSessionHasNoErrors();
    }

    public function test_update_rejects_email_used_by_another_user(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)->put(route('admin.nasabah.update', $customer), [
            'name' => 'Siti',
            'address' => 'Jl. Mawar',
            'email' => 'petugas@test.com',
        ])->assertSessionHasErrors('email');

        $this->assertSame('siti@test.com', $customer->user->fresh()->email);
    }

    public function test_deactivate_only_deactivates_and_is_idempotent(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)
            ->patch(route('admin.nasabah.deactivate', $customer))
            ->assertRedirect(route('admin.nasabah.show', $customer))
            ->assertSessionHas('success', 'Nasabah berhasil dinonaktifkan.');

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('users', ['id' => $customer->user_id]);

        // Panggilan kedua TIDAK boleh mengaktifkan kembali (idempoten)
        $this->actingAs($this->admin)
            ->patch(route('admin.nasabah.deactivate', $customer))
            ->assertRedirect(route('admin.nasabah.show', $customer))
            ->assertSessionHas('success', 'Nasabah berhasil dinonaktifkan.');

        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_admin_can_activate_customer_via_separate_action(): void
    {
        $customer = $this->makeCustomer();
        $customer->is_active = false;
        $customer->save();

        $this->actingAs($this->admin)
            ->patch(route('admin.nasabah.activate', $customer))
            ->assertRedirect(route('admin.nasabah.show', $customer))
            ->assertSessionHas('success', 'Nasabah berhasil diaktifkan kembali.');

        $this->assertTrue($customer->fresh()->is_active);

        // Idempoten jika dipanggil ulang
        $this->actingAs($this->admin)
            ->patch(route('admin.nasabah.activate', $customer))
            ->assertRedirect(route('admin.nasabah.show', $customer));

        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_hard_delete_route_is_not_available(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin)
            ->delete("/admin/nasabah/{$customer->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    // ─── Validasi ───────────────────────────────────────────────

    public function test_store_requires_mandatory_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), [])
            ->assertSessionHasErrors(['name', 'address', 'email', 'password']);

        $this->assertSame(0, Customer::count());
        $this->assertSame(3, User::count());
    }

    public function test_store_shows_indonesian_validation_message(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['name' => '']))
            ->assertSessionHasErrors(['name' => 'Kolom nama wajib diisi.']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['email' => 'petugas@test.com']))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, Customer::count());
    }

    public function test_store_rejects_invalid_email_format(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['email' => 'bukan-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_store_rejects_short_or_unconfirmed_password(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['password' => '123', 'password_confirmation' => '123']))
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['password_confirmation' => 'berbeda123']))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Customer::count());
    }

    public function test_store_rejects_invalid_coordinates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['latitude' => '120', 'longitude' => 'abc']))
            ->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_store_requires_coordinates_in_pairs(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.nasabah.store'), $this->validPayload(['latitude' => '-6.2', 'longitude' => '']))
            ->assertSessionHasErrors('longitude');
    }

    // ─── Hak & Pembatasan Petugas (PBI-02b) ────────────────────

    public function test_petugas_can_view_and_search_customer_list(): void
    {
        $this->makeCustomer('Siti Aminah', 'siti@test.com');
        $this->makeCustomer('Joko Prasetyo', 'joko@test.com', 'Jl. Kenanga No. 3');

        $this->actingAs($this->petugas)
            ->get(route('petugas.nasabah.index'))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('Joko Prasetyo');

        $this->actingAs($this->petugas)
            ->get(route('petugas.nasabah.index', ['q' => 'Joko']))
            ->assertOk()
            ->assertSee('Joko Prasetyo')
            ->assertDontSee('Siti Aminah');
    }

    public function test_petugas_can_create_customer_and_user_account(): void
    {
        $this->actingAs($this->petugas)
            ->get(route('petugas.nasabah.create'))
            ->assertOk()
            ->assertSee('Tambah Nasabah');

        $response = $this->actingAs($this->petugas)
            ->post(route('petugas.nasabah.store'), $this->validPayload());

        $user = User::where('email', 'budi@test.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('nasabah', $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        $customer = $user->customer;
        $this->assertNotNull($customer);
        $this->assertEquals(0, (float) $customer->balance);
        $this->assertTrue($customer->is_active);

        $response->assertRedirect(route('petugas.nasabah.show', $customer));
    }

    public function test_petugas_cannot_access_admin_customer_routes_or_modify_customer(): void
    {
        $customer = $this->makeCustomer();

        // Route admin sepenuhnya terlarang untuk petugas
        $this->actingAs($this->petugas)->get(route('admin.nasabah.index'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('admin.nasabah.edit', $customer))->assertForbidden();
        $this->actingAs($this->petugas)
            ->put(route('admin.nasabah.update', $customer), ['name' => 'Diubah', 'address' => 'X', 'email' => 'x@test.com'])
            ->assertForbidden();
        $this->actingAs($this->petugas)->patch(route('admin.nasabah.deactivate', $customer))->assertForbidden();

        $customer->is_active = false;
        $customer->save();
        $this->actingAs($this->petugas)->patch(route('admin.nasabah.activate', $customer))->assertForbidden();

        // Route petugas tidak menyediakan edit/update/deactivate/activate
        $this->actingAs($this->petugas)->get("/petugas/nasabah/{$customer->id}/edit")->assertNotFound();
        $this->actingAs($this->petugas)->put("/petugas/nasabah/{$customer->id}", ['name' => 'Diubah'])->assertStatus(405);

        $this->assertSame('Siti Aminah', $customer->fresh()->name);
        $this->assertFalse($customer->fresh()->is_active);
    }

    // ─── Penolakan akses Nasabah ───────────────────────────────

    public function test_nasabah_cannot_access_customer_management(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->nasabahUser)->get(route('admin.nasabah.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('admin.nasabah.show', $customer))->assertForbidden();
        $this->actingAs($this->nasabahUser)->post(route('admin.nasabah.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($this->nasabahUser)->patch(route('admin.nasabah.deactivate', $customer))->assertForbidden();
        $this->actingAs($this->nasabahUser)->patch(route('admin.nasabah.activate', $customer))->assertForbidden();
        $this->actingAs($this->nasabahUser)->get(route('petugas.nasabah.index'))->assertForbidden();
        $this->actingAs($this->nasabahUser)->post(route('petugas.nasabah.store'), $this->validPayload())->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'budi@test.com']);
        $this->assertTrue($customer->fresh()->is_active);
    }

    // ─── Penolakan akses Guest ─────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $customer = $this->makeCustomer();

        $this->get(route('admin.nasabah.index'))->assertRedirect(route('login'));
        $this->get(route('admin.nasabah.create'))->assertRedirect(route('login'));
        $this->post(route('admin.nasabah.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->get(route('admin.nasabah.show', $customer))->assertRedirect(route('login'));
        $this->patch(route('admin.nasabah.deactivate', $customer))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'budi@test.com']);
    }
}
