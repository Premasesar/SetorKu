<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompletePickupRequest;
use App\Models\PickupRequest;
use App\Models\WasteType;
use App\Services\PickupService;
use Illuminate\Http\Request;

/**
 * Daftar Tugas & Pencatatan Hasil Penjemputan oleh Petugas — PBI-09.
 */
class OfficerPickupController extends Controller
{
    public function __construct(private readonly PickupService $pickupService)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isPetugas(), 403);

        $pickups = PickupRequest::with('customer')
            ->where('officer_id', $request->user()->id)
            ->where('status', PickupRequest::STATUS_TERJADWAL)
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->paginate(10);

        return view('pickups.petugas.index', compact('pickups'));
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
