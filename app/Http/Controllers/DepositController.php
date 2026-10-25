<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepositRequest;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\WasteType;
use App\Services\DepositService;
use Illuminate\Http\Request;

/**
 * Transaksi Setoran Sampah — PBI-04 / FR-004.
 *
 * - Petugas: lihat riwayat, cari nasabah & catat setoran baru, lihat bukti setoran.
 * - Admin: hanya melihat riwayat dan bukti setoran.
 */
class DepositController extends Controller
{
    public function __construct(private readonly DepositService $depositService)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $deposits = Deposit::with(['customer', 'wasteType', 'officer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                        if (preg_match('/^NSB-0*(\d+)$/i', $search, $m)) {
                            $cq->orWhere('id', (int) $m[1]);
                        }
                    })->orWhereHas('wasteType', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%");
                    });

                    if (preg_match('/^STR-0*(\d+)$/i', $search, $m)) {
                        $q->orWhere('id', (int) $m[1]);
                    }
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('deposits.index', compact('deposits', 'search'));
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

        $wasteTypes = WasteType::active()->orderBy('name')->get();

        return view('deposits.create', compact(
            'customers',
            'wasteTypes',
            'customerSearch',
            'selectedCustomerId',
        ));
    }

    public function store(StoreDepositRequest $request)
    {
        $validated = $request->validated();

        $deposit = $this->depositService->recordDeposit(
            customerId: (int) $validated['customer_id'],
            wasteTypeId: (int) $validated['waste_type_id'],
            weight: $validated['weight'],
            officer: $request->user(),
        );

        return redirect()
            ->route('petugas.setoran.show', $deposit)
            ->with('success', 'Setoran berhasil dicatat dan saldo nasabah telah diperbarui.');
    }

    public function show(Request $request, Deposit $deposit)
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isPetugas(), 403);

        $deposit->load(['customer', 'wasteType', 'officer']);

        return view('deposits.show', compact('deposit'));
    }
}
