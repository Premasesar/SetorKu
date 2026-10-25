<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WasteType;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-12: Kartu QR Nasabah & Pencarian Cepat Kode Nasabah.
 */
class CustomerQrCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $nasabahUserA;
    private User $nasabahUserB;
    private Customer $customerA;
    private Customer $customerB;
    private WasteType $wasteType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->petugas = User::create([
            'name' => 'Petugas Satu', 'email' => 'petugas@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);

        $this->nasabahUserA = User::create([
            'name' => 'Budi Santoso', 'email' => 'rahasia-budi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerA = $this->nasabahUserA->customer()->create([
            'name' => 'Budi Santoso',
            'phone' => '081999888777',
            'address' => 'Alamat Rahasia Blok Z99 Yogyakarta',
            'latitude' => -7.7925760,
            'longitude' => 110.3658430,
        ]);
        $this->customerA->balance = 777888.50;
        $this->customerA->save();

        $this->nasabahUserB = User::create([
            'name' => 'Siti Aminah', 'email' => 'siti@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customerB = $this->nasabahUserB->customer()->create([
            'name' => 'Siti Aminah',
            'address' => 'Jl. Kaliurang Km 5, Sleman',
        ]);

        $this->wasteType = WasteType::create([
            'name' => 'Plastik PET',
            'unit' => 'kg',
            'price_per_unit' => 3500.00,
        ]);
    }

    public function test_qr_card_contains_only_customer_code_and_no_balance_or_sensitive_data(): void
    {
        $response = $this->actingAs($this->nasabahUserA)->get(route('nasabah.kartu.show'));

        $response->assertOk()
            ->assertSee('Kartu Saya')
            ->assertSee('Budi Santoso')
            ->assertSee($this->customerA->code)
            ->assertSee('data-qr-payload="' . $this->customerA->code . '"', false)
            ->assertSee('cdnjs.cloudflare.com/ajax/libs/qrcodejs', false)
            ->assertSee('@media print', false)
            // Pastikan saldo dan data sensitif TIDAK ada di halaman maupun payload QR
            ->assertDontSee('777.888')
            ->assertDontSee('777888')
            ->assertDontSee('rahasia-budi@test.com')
            ->assertDontSee('081999888777')
            ->assertDontSee('Alamat Rahasia Blok Z99');
    }

    public function test_role_access_and_customer_cannot_open_another_customer_card(): void
    {
        // Nasabah A boleh membuka kartunya sendiri, tetapi mendapat 403 untuk kartu Nasabah B
        $this->actingAs($this->nasabahUserA)
            ->get(route('nasabah.kartu.show', $this->customerA))
            ->assertOk();

        $this->actingAs($this->nasabahUserA)
            ->get(route('nasabah.kartu.show', $this->customerB))
            ->assertForbidden();

        $this->actingAs($this->nasabahUserA)
            ->get(route('admin.nasabah.card', $this->customerB))
            ->assertForbidden();

        $this->actingAs($this->nasabahUserA)
            ->get(route('petugas.nasabah.card', $this->customerB))
            ->assertForbidden();

        // Admin dan Petugas melihat tombol "Cetak kartu" di detail nasabah & dapat membuka halaman kartu
        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.show', $this->customerA))
            ->assertOk()
            ->assertSee('Cetak kartu');

        $this->actingAs($this->admin)
            ->get(route('admin.nasabah.card', $this->customerA))
            ->assertOk()
            ->assertSee('data-qr-payload="' . $this->customerA->code . '"', false);

        $this->actingAs($this->petugas)
            ->get(route('petugas.nasabah.show', $this->customerA))
            ->assertOk()
            ->assertSee('Cetak kartu');

        $this->actingAs($this->petugas)
            ->get(route('petugas.nasabah.card', $this->customerA))
            ->assertOk()
            ->assertSee('data-qr-payload="' . $this->customerA->code . '"', false);

        // Admin dan Petugas tidak bisa mengakses route khusus portal nasabah
        $this->actingAs($this->admin)->get(route('nasabah.kartu.show'))->assertForbidden();
        $this->actingAs($this->petugas)->get(route('nasabah.kartu.show'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('nasabah.kartu.show'))->assertRedirect(route('login'));
        $this->get(route('admin.nasabah.card', $this->customerA))->assertRedirect(route('login'));
        $this->get(route('petugas.nasabah.card', $this->customerA))->assertRedirect(route('login'));
    }

    public function test_invalid_code_shows_not_found_and_inactive_customer_cannot_be_selected(): void
    {
        // Kolom scanner autofocus & tombol Scan dengan kamera tersedia di form setoran & penarikan
        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create'))
            ->assertOk()
            ->assertSee('id="qr-quick-code"', false)
            ->assertSee('autofocus', false)
            ->assertSee('Scan dengan kamera')
            ->assertSee('cdnjs.cloudflare.com/ajax/libs/html5-qrcode', false);

        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.create'))
            ->assertOk()
            ->assertSee('id="qr-quick-code"', false)
            ->assertSee('autofocus', false)
            ->assertSee('Scan dengan kamera');

        // Kode aktif valid -> terpilih otomatis
        $validResp = $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create', ['q' => $this->customerA->code]));
        $validResp->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Nasabah tidak ditemukan');
        $this->assertSame($this->customerA->id, $validResp->viewData('selectedCustomerId'));

        // Kode tidak valid -> tampil pesan "Nasabah tidak ditemukan" di Setoran & Penarikan
        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create', ['q' => 'NSB-9999']))
            ->assertOk()
            ->assertSee('Nasabah tidak ditemukan');

        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.create', ['q' => 'QR-TIDAK-VALID']))
            ->assertOk()
            ->assertSee('Nasabah tidak ditemukan');

        // Nonaktifkan Nasabah B -> pencarian kode QR miliknya tidak muncul di pilihan & menampilkan "Nasabah tidak ditemukan"
        $this->customerB->is_active = false;
        $this->customerB->balance = 50000.00;
        $this->customerB->save();

        $this->actingAs($this->petugas)
            ->get(route('petugas.setoran.create', ['q' => $this->customerB->code]))
            ->assertOk()
            ->assertSee('Nasabah tidak ditemukan')
            ->assertDontSee('Siti Aminah');

        $this->actingAs($this->petugas)
            ->get(route('petugas.penarikan.create', ['q' => $this->customerB->code]))
            ->assertOk()
            ->assertSee('Nasabah tidak ditemukan')
            ->assertDontSee('Siti Aminah');

        // Pengiriman paksa ID nasabah nonaktif untuk setoran maupun penarikan tetap ditolak di backend
        $this->actingAs($this->petugas)
            ->post(route('petugas.setoran.store'), [
                'customer_id' => $this->customerB->id,
                'waste_type_id' => $this->wasteType->id,
                'weight' => '2.000',
            ])
            ->assertSessionHasErrors('customer_id');

        $this->actingAs($this->petugas)
            ->post(route('petugas.penarikan.store'), [
                'customer_id' => $this->customerB->id,
                'amount' => '10000.00',
            ])
            ->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Deposit::count());
        $this->assertSame(0, Withdrawal::count());
    }
}
