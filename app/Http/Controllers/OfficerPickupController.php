<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompletePickupRequest;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use App\Services\PickupService;
use App\Services\RouteOptimizationService;
use Illuminate\Http\Request;

/**
 * Daftar Tugas, Peta Rute Read-Only & Pencatatan Hasil Penjemputan oleh Petugas — PBI-09 & PBI-11.
 */
class OfficerPickupController extends Controller
{
    public function __construct(
        private readonly PickupService $pickupService,
        private readonly RouteOptimizationService $routeService,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isPetugas(), 403);

        if ($request->filled('officer_id') && (int) $request->query('officer_id') !== (int) $request->user()->id) {
            abort(403, 'Anda tidak diizinkan melihat rute petugas lain.');
        }

        $pickups = PickupRequest::with('customer')
            ->where('officer_id', $request->user()->id)
            ->where('status', PickupRequest::STATUS_TERJADWAL)
            ->orderBy('scheduled_date')
            ->orderByRaw('CASE WHEN route_sequence IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('route_sequence')
            ->orderBy('id')
            ->paginate(10);

        $todayRoute = $this->routeService->buildOfficerReadOnlyRoute(
            officer: $request->user(),
            date: now()->toDateString(),
        );

        return view('pickups.petugas.index', compact('pickups', 'todayRoute'));
    }

    public function route(Request $request, ?User $officer = null)
    {
        abort_unless($request->user()?->isPetugas(), 403);

        $targetOfficerId = $officer !== null
            ? (int) $officer->id
            : ($request->filled('officer_id') ? (int) $request->query('officer_id') : (int) $request->user()->id);

        abort_unless($targetOfficerId === (int) $request->user()->id, 403, 'Anda tidak diizinkan melihat rute petugas lain.');

        $date = (string) $request->query('date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }

        $todayRoute = $this->routeService->buildOfficerReadOnlyRoute(
            officer: $request->user(),
            date: $date,
        );

        return view('pickups.petugas.route', compact('todayRoute'));
    }

    public function show(Request $request, PickupRequest $pickupRequest)
    {
        abort_unless($request->user()?->isPetugas(), 403);
        abort_unless((int) $pickupRequest->officer_id === (int) $request->user()->id, 403);

        $pickupRequest->load(['customer', 'deposits.wasteType']);
        $wasteTypes = WasteType::active()->orderBy('name')->get();

        return view('pickups.petugas.show', compact('pickupRequest', 'wasteTypes'));
    }

    public function complete(CompletePickupRequest $request, PickupRequest $pickupRequest)
    {
        $items = array_values($request->validated('items'));

        $deposits = $this->pickupService->completePickup(
            pickupRequest: $pickupRequest,
            items: $items,
            officer: $request->user(),
        );

        return redirect()
            ->route('petugas.penjemputan.show', $pickupRequest)
            ->with('success', "Penjemputan {$pickupRequest->code} selesai dicatat ({$deposits->count()} setoran dibuat).");
    }
}
