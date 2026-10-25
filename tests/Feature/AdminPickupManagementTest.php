<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use App\Services\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test PBI-08: Kelola Penjemputan oleh Admin.
 */
class AdminPickupManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $activeOfficer1;
    private User $activeOfficer2;
    private User $inactiveOfficer;
    private User $nasabahUser;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Utama', 'email' => 'admin@test.com', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->activeOfficer1 = User::create([
            'name' => 'Petugas Aktif 1', 'email' => 'p1@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->activeOfficer2 = User::create([
            'name' => 'Petugas Aktif 2', 'email' => 'p2@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->inactiveOfficer = User::create([
            'name' => 'Petugas Nonaktif', 'email' => 'p_off@test.com', 'password' => 'password', 'role' => 'petugas',
        ]);
        $this->inactiveOfficer->is_active = false;
        $this->inactiveOfficer->save();

        $this->nasabahUser = User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@test.com', 'password' => 'password', 'role' => 'nasabah',
        ]);
        $this->customer = $this->nasabahUser->customer()->create([
            'name' => 'Budi Santoso',
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
            'latitude' => -7.7925760,
            'longitude' => 110.3658430,
        ]);
    }

    private function createPickup(string $status = PickupRequest::STATUS_MENUNGGU, ?string $date = null): PickupRequest
    {
        $pickup = app(PickupService::class)->submitRequest($this->customer, [
            'requested_date' => $date ?? now()->toDateString(),
            'address' => $this->customer->address,
            'latitude' => -7.7925760,
            'longitude' => 110.3658430,
            'notes' => 'Sampah plastik & kardus',
        ]);

        if ($status !== PickupRequest::STATUS_MENUNGGU) {
            $pickup->status = $status;
            if ($status === PickupRequest::STATUS_TERJADWAL) {
                $pickup->scheduled_date = now()->toDateString();
                $pickup->officer_id = $this->activeOfficer1->id;
                $pickup->scheduled_by = $this->admin->id;
                $pickup->scheduled_at = now();
            } elseif ($status === PickupRequest::STATUS_DITOLAK) {
                $pickup->rejection_reason = 'Armada penuh';
            } elseif ($status === PickupRequest::STATUS_SELESAI) {
                $pickup->scheduled_date = now()->toDateString();
                $pickup->officer_id = $this->activeOfficer1->id;
                $pickup->completed_at = now();
            }
            $pickup->save();
        }

        return $pickup;
    }

    public function test_admin_can_list_and_filter_pickups_by_status_and_date(): void
    {
        $today = now()->toDateString();
        $nextWeek = now()->addDays(7)->toDateString();

        $pWait = $this->createPickup(PickupRequest::STATUS_MENUNGGU, $today);
        $pSched = $this->createPickup(PickupRequest::STATUS_TERJADWAL, $nextWeek);
        $pSched->scheduled_date = $nextWeek;
        $pSched->save();

        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.index', ['status' => 'menunggu']))
            ->assertOk()
            ->assertSee($pWait->code)
            ->assertDontSee($pSched->code);

        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.index', ['date' => $nextWeek]))
            ->assertOk()
            ->assertSee($pSched->code)
            ->assertDontSee($pWait->code);
    }

    public function test_valid_transitions_reject_schedule_and_reschedule(): void
    {
        // 1. menunggu -> ditolak (alasan wajib)
        $pReject = $this->createPickup(PickupRequest::STATUS_MENUNGGU);
        $this->actingAs($this->admin)
            ->patch(route('admin.penjemputan.reject', $pReject), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($this->admin)
            ->patch(route('admin.penjemputan.reject', $pReject), ['rejection_reason' => 'Di luar jangkauan layanan'])
            ->assertRedirect(route('admin.penjemputan.show', $pReject));

        $pReject->refresh();
        $this->assertSame(PickupRequest::STATUS_DITOLAK, $pReject->status);
        $this->assertSame('Di luar jangkauan layanan', $pReject->rejection_reason);

        // 2. menunggu -> terjadwal (catat scheduled_by & scheduled_at)
        $pSchedule = $this->createPickup(PickupRequest::STATUS_MENUNGGU);
        $schedDate1 = now()->addDay()->toDateString();

        $this->actingAs($this->admin)
            ->patch(route('admin.penjemputan.schedule', $pSchedule), [
                'scheduled_date' => $schedDate1,
                'officer_id' => $this->activeOfficer1->id,
            ])
            ->assertRedirect(route('admin.penjemputan.show', $pSchedule));

        $pSchedule->refresh();
        $this->assertSame(PickupRequest::STATUS_TERJADWAL, $pSchedule->status);
        $this->assertSame($schedDate1, $pSchedule->scheduled_date->toDateString());
        $this->assertSame($this->activeOfficer1->id, $pSchedule->officer_id);
        $this->assertSame($this->admin->id, $pSchedule->scheduled_by);
        $this->assertNotNull($pSchedule->scheduled_at);

        // 3. terjadwal -> ubah jadwal (tanggal & petugas)
        $schedDate2 = now()->addDays(2)->toDateString();
        $this->actingAs($this->admin)
            ->patch(route('admin.penjemputan.reschedule', $pSchedule), [
                'scheduled_date' => $schedDate2,
                'officer_id' => $this->activeOfficer2->id,
            ])
            ->assertRedirect(route('admin.penjemputan.show', $pSchedule));

        $pSchedule->refresh();
        $this->assertSame(PickupRequest::STATUS_TERJADWAL, $pSchedule->status);
        $this->assertSame($schedDate2, $pSchedule->scheduled_date->toDateString());
        $this->assertSame($this->activeOfficer2->id, $pSchedule->officer_id);
        $this->assertSame($this->admin->id, $pSchedule->scheduled_by);
    }

    public function test_invalid_state_transitions_and_past_dates_are_rejected(): void
    {
        $pWaiting = $this->createPickup(PickupRequest::STATUS_MENUNGGU);
        $pRejected = $this->createPickup(PickupRequest::STATUS_DITOLAK);
        $pScheduled = $this->createPickup(PickupRequest::STATUS_TERJADWAL);
        $pCompleted = $this->createPickup(PickupRequest::STATUS_SELESAI);

        $tomorrow = now()->addDay()->toDateString();

        // Tanggal jadwal lampau ditolak
        $this->actingAs($this->admin)->patch(route('admin.penjemputan.schedule', $pWaiting), [
            'scheduled_date' => now()->subDay()->toDateString(),
            'officer_id' => $this->activeOfficer1->id,
        ])->assertSessionHasErrors('scheduled_date');

        // Tolak dari selain 'menunggu' ditolak dengan error 'status'
        foreach ([$pRejected, $pScheduled, $pCompleted] as $p) {
            $this->actingAs($this->admin)
                ->patch(route('admin.penjemputan.reject', $p), ['rejection_reason' => 'Alasan'])
                ->assertSessionHasErrors('status');
        }

        // Jadwalkan dari selain 'menunggu' ditolak dengan error 'status'
        foreach ([$pRejected, $pScheduled, $pCompleted] as $p) {
            $this->actingAs($this->admin)
                ->patch(route('admin.penjemputan.schedule', $p), [
                    'scheduled_date' => $tomorrow,
                    'officer_id' => $this->activeOfficer1->id,
                ])
                ->assertSessionHasErrors('status');
        }

        // Ubah jadwal dari selain 'terjadwal' ditolak dengan error 'status'
        foreach ([$pWaiting, $pRejected, $pCompleted] as $p) {
            $this->actingAs($this->admin)
                ->patch(route('admin.penjemputan.reschedule', $p), [
                    'scheduled_date' => $tomorrow,
                    'officer_id' => $this->activeOfficer2->id,
                ])
                ->assertSessionHasErrors('status');
        }

        // Admin tidak memiliki aksi menyelesaikan penjemputan
        $this->actingAs($this->admin)
            ->patch("/admin/penjemputan/{$pScheduled->id}/selesai")
            ->assertNotFound();
    }

    public function test_inactive_officer_or_non_officer_cannot_be_selected(): void
    {
        $pWaiting = $this->createPickup(PickupRequest::STATUS_MENUNGGU);

        // Halaman detail hanya memuat petugas aktif, tidak memuat petugas nonaktif
        $this->actingAs($this->admin)
            ->get(route('admin.penjemputan.show', $pWaiting))
            ->assertOk()
            ->assertSee('Petugas Aktif 1')
            ->assertDontSee('Petugas Nonaktif')
            ->assertSee('unpkg.com/leaflet', false);

        // Kirim ID petugas nonaktif, admin, atau nasabah -> ditolak
        foreach ([$this->inactiveOfficer->id, $this->admin->id, $this->nasabahUser->id] as $invalidOfficerId) {
            $this->actingAs($this->admin)->patch(route('admin.penjemputan.schedule', $pWaiting), [
                'scheduled_date' => now()->toDateString(),
                'officer_id' => $invalidOfficerId,
            ])->assertSessionHasErrors('officer_id');
        }

        $this->assertSame(PickupRequest::STATUS_MENUNGGU, $pWaiting->fresh()->status);
    }

    public function test_petugas_and_nasabah_get_403_and_guest_redirects(): void
    {
        $pickup = $this->createPickup(PickupRequest::STATUS_MENUNGGU);

        foreach ([$this->activeOfficer1, $this->nasabahUser] as $unauthorized) {
            $this->actingAs($unauthorized)->get(route('admin.penjemputan.index'))->assertForbidden();
            $this->actingAs($unauthorized)->get(route('admin.penjemputan.show', $pickup))->assertForbidden();
            $this->actingAs($unauthorized)->patch(route('admin.penjemputan.reject', $pickup), [
                'rejection_reason' => 'X',
            ])->assertForbidden();
            $this->actingAs($unauthorized)->patch(route('admin.penjemputan.schedule', $pickup), [
                'scheduled_date' => now()->toDateString(),
                'officer_id' => $this->activeOfficer1->id,
            ])->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.penjemputan.index'))->assertRedirect(route('login'));
    }
}
