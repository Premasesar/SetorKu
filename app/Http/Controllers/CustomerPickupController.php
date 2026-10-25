<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePickupRequest;
use App\Models\Customer;
use App\Models\PickupRequest;
use App\Services\PickupService;
use Illuminate\Http\Request;

/**
 * Pengajuan Penjemputan Sampah oleh Nasabah — PBI-07.
 */
class CustomerPickupController extends Controller
{
    public function __construct(private readonly PickupService $pickupService)
    {
    }

    public function index(Request $request)
    {
        $customer = $this->requireOwnCustomer($request);

        $pickups = $customer->pickupRequests()
            ->with('officer')
            ->latest('id')
            ->paginate(10);

        return view('pickups.nasabah.index', compact('customer', 'pickups'));
    }

    public function create(Request $request)
    {
        $customer = $this->requireOwnCustomer($request);

        return view('pickups.nasabah.create', compact('customer'));
    }

    public function store(StorePickupRequest $request)
    {
        $customer = $this->requireOwnCustomer($request);

        $pickup = $this->pickupService->submitRequest($customer, $request->validated());

        return redirect()
            ->route('nasabah.penjemputan.show', $pickup)
            ->with('success', 'Pengajuan penjemputan berhasil dikirim dan menunggu konfirmasi admin.');
    }

    public function show(Request $request, PickupRequest $pickupRequest)
    {
        $customer = $this->requireOwnCustomer($request);

        abort_unless((int) $pickupRequest->customer_id === (int) $customer->id, 403);

        $pickupRequest->load(['customer', 'officer', 'scheduler']);

        return view('pickups.nasabah.show', compact('pickupRequest'));
    }

    private function requireOwnCustomer(Request $request): Customer
    {
        abort_unless($request->user()?->isNasabah(), 403);

        $customer = $request->user()->customer;
        abort_if($customer === null, 404, 'Profil nasabah belum tersedia.');

        return $customer;
    }
}
