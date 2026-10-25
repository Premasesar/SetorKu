<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectPickupRequest;
use App\Http\Requests\SchedulePickupRequest;
use App\Models\PickupRequest;
use App\Models\User;
use App\Services\PickupService;
use Illuminate\Http\Request;

/**
 * Pengelolaan & Penjadwalan Penjemputan Sampah oleh Admin — PBI-08.
 */
class AdminPickupController extends Controller
{
    public function __construct(private readonly PickupService $pickupService)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $status = in_array($request->query('status'), PickupRequest::STATUSES, true)
            ? (string) $request->query('status')
            : '';
        $date = trim((string) $request->query('date', ''));
        $search = trim((string) $request->query('q', ''));

        $pickups = PickupRequest::with(['customer', 'officer'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($date !== '', function ($q) use ($date) {
                $q->where(function ($sub) use ($date) {
                    $sub->whereDate('requested_date', $date)
                        ->orWhereDate('scheduled_date', $date);
                });
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                    if (preg_match('/^JMP-0*(\d+)$/i', $search, $m)) {
                        $sub->orWhere('id', (int) $m[1]);
                    }
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pickups.admin.index', compact('pickups', 'status', 'date', 'search'));
    }

    public function show(Request $request, PickupRequest $pickupRequest)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $pickupRequest->load(['customer', 'officer', 'scheduler']);
        $activeOfficers = User::activeOfficers()->orderBy('name')->get();

        return view('pickups.admin.show', compact('pickupRequest', 'activeOfficers'));
    }

    public function reject(RejectPickupRequest $request, PickupRequest $pickupRequest)
    {
        $this->pickupService->reject(
            pickupRequest: $pickupRequest,
            reason: $request->validated('rejection_reason'),
            admin: $request->user(),
        );

        return redirect()
            ->route('admin.penjemputan.show', $pickupRequest)
            ->with('success', 'Pengajuan penjemputan telah ditolak.');
    }

    public function schedule(SchedulePickupRequest $request, PickupRequest $pickupRequest)
    {
        $validated = $request->validated();

        $this->pickupService->schedule(
            pickupRequest: $pickupRequest,
            scheduledDate: $validated['scheduled_date'],
            officerId: (int) $validated['officer_id'],
            admin: $request->user(),
        );

        return redirect()
            ->route('admin.penjemputan.show', $pickupRequest)
            ->with('success', 'Penjemputan berhasil dijadwalkan.');
    }

    public function reschedule(SchedulePickupRequest $request, PickupRequest $pickupRequest)
    {
        $validated = $request->validated();

        $this->pickupService->reschedule(
            pickupRequest: $pickupRequest,
            scheduledDate: $validated['scheduled_date'],
            officerId: (int) $validated['officer_id'],
            admin: $request->user(),
        );

        return redirect()
            ->route('admin.penjemputan.show', $pickupRequest)
            ->with('success', 'Jadwal penjemputan berhasil diperbarui.');
    }
}
