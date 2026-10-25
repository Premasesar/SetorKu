<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\PickupRequest;
use App\Models\Withdrawal;
use App\Services\CustomerPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Portal Mandiri Nasabah — PBI-06 / FR-006.
 *
 * Seluruh data diambil berdasarkan akun nasabah yang sedang login.
 * Nasabah tidak pernah diizinkan mengakses atau mengubah data nasabah lain.
 */
class CustomerPortalController extends Controller
{
    public function __construct(private readonly CustomerPortalService $portalService)
    {
    }

    public function dashboard(Request $request)
    {
        abort_unless($request->user()?->isNasabah(), 403);

        $customer = $request->user()->customer;
        $recentTransactions = $customer !== null
            ? $this->portalService->recentTransactions($customer, 5)
            : collect();

        $totalDeposits = $customer !== null ? (float) $customer->deposits()->sum('total_value') : 0.0;
        $totalWithdrawals = $customer !== null ? (float) $customer->withdrawals()->sum('amount') : 0.0;

        $activePickup = $customer?->pickupRequests()
            ->with('officer')
            ->whereIn('status', [PickupRequest::STATUS_MENUNGGU, PickupRequest::STATUS_TERJADWAL])
            ->latest('id')
            ->first();

        return view('dashboard.nasabah', compact(
            'customer',
            'recentTransactions',
            'totalDeposits',
            'totalWithdrawals',
            'activePickup',
        ));
    }

    public function transactions(Request $request)
    {
        $customer = $this->requireOwnCustomer($request);

        $type = in_array($request->query('type'), ['all', 'setoran', 'penarikan'], true)
            ? (string) $request->query('type')
            : 'all';

        $transactions = $this->portalService->paginatedTransactions($customer, 10, $type);

        return view('portal.transactions', compact('customer', 'transactions', 'type'));
    }

    public function showDeposit(Request $request, Deposit $deposit)
    {
        $customer = $this->requireOwnCustomer($request);

        abort_unless((int) $deposit->customer_id === (int) $customer->id, 403);

        $deposit->load(['customer', 'wasteType', 'officer', 'pickupRequest']);

        return view('portal.show-deposit', compact('deposit'));
    }

    public function showWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        $customer = $this->requireOwnCustomer($request);

        abort_unless((int) $withdrawal->customer_id === (int) $customer->id, 403);

        $withdrawal->load(['customer', 'officer']);

        return view('portal.show-withdrawal', compact('withdrawal'));
    }

    public function editProfile(Request $request)
    {
        $customer = $this->requireOwnCustomer($request);

        return view('portal.profile', compact('customer'));
    }

    public function updateProfile(UpdateCustomerProfileRequest $request)
    {
        $user = $request->user();
        $customer = $user->customer;
        $data = $request->validated();

        DB::transaction(function () use ($user, $customer, $data) {
            $customer->update([
                'name' => $data['name'],
                'address' => $data['address'],
                'phone' => $data['phone'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $user->update([
                'name' => $data['name'],
            ]);
        });

        return redirect()
            ->route('nasabah.profil.edit')
            ->with('success', 'Profil Anda berhasil diperbarui.');
    }

    private function requireOwnCustomer(Request $request): Customer
    {
        abort_unless($request->user()?->isNasabah(), 403);

        $customer = $request->user()->customer;
        abort_if($customer === null, 404, 'Profil nasabah belum tersedia.');

        return $customer;
    }
}
