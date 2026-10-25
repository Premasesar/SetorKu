<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\Customer;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

/**
 * Transaksi Penarikan Saldo — PBI-05 / FR-005.
 *
 * - Petugas: lihat riwayat, cari nasabah & catat penarikan baru, lihat bukti penarikan.
 * - Admin: hanya melihat riwayat dan bukti penarikan.
 */
class WithdrawalController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $withdrawals = Withdrawal::with(['customer', 'officer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                        if (preg_match('/^NSB-0*(\d+)$/i', $search, $m)) {
                            $cq->orWhere('id', (int) $m[1]);
                        }
                    });

                    if (preg_match('/^TRK-0*(\d+)$/i', $search, $m)) {
                        $q->orWhere('id', (int) $m[1]);
                    }
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('withdrawals.index', compact('withdrawals', 'search'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()?->isPetugas(), 403);

        $customerSearch = trim((string) $request->query('q', ''));
        $selectedCustomerId = $request->integer('customer_id') ?: null;

        $customers = Customer::active()
            ->when($customerSearch !== '', function ($query) use ($customerSearch) {
                $query->where(function ($q) use ($customerSearch) {
                    $q->where('name', 'like', "%{$customerSearch}%");

                    if (preg_match('/^NSB-0*(\d+)$/i', $customerSearch, $m)) {
                        $q->orWhere('id', (int) $m[1]);
                    } elseif (ctype_digit($customerSearch)) {
                        $q->orWhere('id', (int) $customerSearch);
                    }
                });
            })
            ->orderBy('name')
            ->limit(100)
            ->get();

        if ($selectedCustomerId === null && $customerSearch !== '' && $customers->count() === 1) {
            $selectedCustomerId = $customers->first()->id;
        }

        return view('withdrawals.create', compact(
            'customers',
            'customerSearch',
            'selectedCustomerId',
        ));
    }

    public function store(StoreWithdrawalRequest $request)
    {
        $validated = $request->validated();

        $withdrawal = $this->withdrawalService->recordWithdrawal(
            customerId: (int) $validated['customer_id'],
            amount: $validated['amount'],
            officer: $request->user(),
        );

        return redirect()
            ->route('petugas.penarikan.show', $withdrawal)
            ->with('success', 'Penarikan saldo berhasil dicatat dan saldo nasabah telah diperbarui.');
    }

    public function show(Request $request, Withdrawal $withdrawal)
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isPetugas(), 403);

        $withdrawal->load(['customer', 'officer']);

        return view('withdrawals.show', compact('withdrawal'));
    }
}
