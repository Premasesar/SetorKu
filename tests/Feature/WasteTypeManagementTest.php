<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteType;
use Database\Seeders\WasteTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-03: Pengelolaan Jenis Sampah & Harga.
 */
class WasteTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->petugas = User::create([
            'name' => 'Petugas', 'email' => 'petugas@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->nasabah = User::create([
            'name' => 'Nasabah', 'email' => 'nasabah@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
    }

    // ─── Akses Admin (CRUD + nonaktifkan/aktifkan) ───────────────

    public function test_admin_can_view_all_waste_types_including_inactive(): void
    {
        WasteType::factory()->create(['name' => 'Plastik PET', 'price_per_unit' => 3500]);
        WasteType::factory()->inactive()->create(['name' => 'Kaca Pecah', 'price_per_unit' => 500]);

        $this->actingAs($this->admin)
            ->get(route('admin.jenis-sampah.index'))
            ->assertOk()
            ->assertSee('Plastik PET')
            ->assertSee('Kaca Pecah');
    }

    public function test_admin_can_create_waste_type_with_default_unit(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.jenis-sampah.store'), [
                'name' => 'Plastik PET',
                'unit' => '',
                'price_per_unit' => '3500.50',
                'is_active' => false, // harus diabaikan
            ]);

        $response->assertRedirect(route('admin.jenis-sampah.index'));
        $response->assertSessionHas('success');

        $wt = WasteType::where('name', 'Plastik PET')->first();
        $this->assertNotNull($wt);
        $this->assertSame('kg', $wt->unit);
        $this->assertEquals(3500.50, (float) $wt->price_per_unit);
        $this->assertTrue($wt->is_active);
    }

    public function test_admin_can_update_waste_type(): void
    {
        $wt = WasteType::factory()->create([
            'name' => 'Kardus',
            'unit' => 'kg',
            'price_per_unit' => 2000,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.jenis-sampah.update', $wt), [
                'name' => 'Kardus Tebal',
                'unit' => 'kg',
                'price_per_unit' => 2500,
            ])
            ->assertRedirect(route('admin.jenis-sampah.index'))
            ->assertSessionHas('success');

        $wt->refresh();
        $this->assertSame('Kardus Tebal', $wt->name);
        $this->assertEquals(2500.00, (float) $wt->price_per_unit);
    }

    public function test_admin_can_deactivate_and_activate_waste_type_idempotently(): void
    {
        $wt = WasteType::factory()->create(['name' => 'Kaleng']);

        $this->actingAs($this->admin)
            ->patch(route('admin.jenis-sampah.deactivate', $wt))
            ->assertRedirect(route('admin.jenis-sampah.index'));
        $this->assertFalse($wt->fresh()->is_active);

        // Panggilan kedua deactivate tetap nonaktif
        $this->actingAs($this->admin)
            ->patch(route('admin.jenis-sampah.deactivate', $wt));
        $this->assertFalse($wt->fresh()->is_active);

        // Aktifkan kembali via route terpisah
        $this->actingAs($this->admin)
            ->patch(route('admin.jenis-sampah.activate', $wt))
            ->assertRedirect(route('admin.jenis-sampah.index'));
        $this->assertTrue($wt->fresh()->is_active);
    }

    public function test_hard_delete_waste_type_is_not_available(): void
    {
        $wt = WasteType::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/admin/jenis-sampah/{$wt->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('waste_types', ['id' => $wt->id]);
    }

    // ─── Validasi ───────────────────────────────────────────────

    public function test_waste_type_validation_rejects_empty_duplicate_zero_or_negative_price(): void
    {
        WasteType::factory()->create(['name' => 'Kertas']);

        // Kosong
        $this->actingAs($this->admin)
            ->post(route('admin.jenis-sampah.store'), [])
            ->assertSessionHasErrors(['name', 'price_per_unit']);

        // Nama duplikat
        $this->actingAs($this->admin)
            ->post(route('admin.jenis-sampah.store'), [
                'name' => 'Kertas',
                'price_per_unit' => 1500,
            ])
            ->assertSessionHasErrors('name');

        // Harga 0 ditolak (> 0)
        $this->actingAs($this->admin)
            ->post(route('admin.jenis-sampah.store'), [
                'name' => 'Besi',
                'price_per_unit' => 0,
            ])
            ->assertSessionHasErrors('price_per_unit');

        // Harga negatif ditolak
        $this->actingAs($this->admin)
            ->post(route('admin.jenis-sampah.store'), [
                'name' => 'Besi',
                'price_per_unit' => -100,
            ])
            ->assertSessionHasErrors('price_per_unit');

        $this->assertSame(1, WasteType::count());
    }

    // ─── Hak & Pembatasan Peran Lain ────────────────────────────

    public function test_petugas_can_only_view_active_waste_types_and_cannot_modify(): void
    {
        $active = WasteType::factory()->create(['name' => 'Plastik PET', 'price_per_unit' => 3500]);
        $inactive = WasteType::factory()->inactive()->create(['name' => 'Limbah Nonaktif', 'price_per_unit' => 900]);

        $this->actingAs($this->petugas)
            ->get(route('petugas.jenis-sampah.index'))
            ->assertOk()
            ->assertSee('Plastik PET')
            ->assertDontSee('Limbah Nonaktif');

        // Semua route admin ditolak (403)
        $this->actingAs($this->petugas)->get(route('admin.jenis-sampah.index'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('admin.jenis-sampah.create'))->assertForbidden();
        $this->actingAs($this->petugas)->post(route('admin.jenis-sampah.store'), [
            'name' => 'Baru', 'price_per_unit' => 1000,
        ])->assertForbidden();
        $this->actingAs($this->petugas)->get(route('admin.jenis-sampah.edit', $active))->assertForbidden();
        $this->actingAs($this->petugas)->put(route('admin.jenis-sampah.update', $active), [
            'name' => 'Ubah', 'price_per_unit' => 5000,
        ])->assertForbidden();
        $this->actingAs($this->petugas)->patch(route('admin.jenis-sampah.deactivate', $active))->assertForbidden();
        $this->actingAs($this->petugas)->patch(route('admin.jenis-sampah.activate', $inactive))->assertForbidden();
    }

    public function test_nasabah_cannot_access_waste_types(): void
    {
        $wt = WasteType::factory()->create();

        $this->actingAs($this->nasabah)->get(route('admin.jenis-sampah.index'))->assertForbidden();
        $this->actingAs($this->nasabah)->get(route('petugas.jenis-sampah.index'))->assertForbidden();
        $this->actingAs($this->nasabah)->post(route('admin.jenis-sampah.store'), [
            'name' => 'X', 'price_per_unit' => 1000,
        ])->assertForbidden();
        $this->actingAs($this->nasabah)->patch(route('admin.jenis-sampah.deactivate', $wt))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.jenis-sampah.index'))->assertRedirect(route('login'));
        $this->get(route('petugas.jenis-sampah.index'))->assertRedirect(route('login'));
    }

    public function test_waste_type_seeder_populates_five_default_items(): void
    {
        $this->seed(WasteTypeSeeder::class);
        $this->seed(WasteTypeSeeder::class); // idempoten

        $this->assertSame(5, WasteType::count());
        foreach (['Plastik PET', 'Kardus', 'Kertas', 'Botol Kaca', 'Kaleng'] as $name) {
            $this->assertDatabaseHas('waste_types', ['name' => $name, 'is_active' => true]);
        }
    }
}
