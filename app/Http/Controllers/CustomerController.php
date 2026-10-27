<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan Data Nasabah — PBI-02 & PBI-02b / FR-002 / UC-02.
 *
 * - Admin: daftar (cari + paginasi), tambah, detail, ubah, nonaktifkan, aktifkan.
 * - Petugas: daftar (cari + paginasi), tambah, detail. Tidak boleh ubah/nonaktifkan/aktifkan.
 * - Saldo (balance) tidak pernah diambil dari input form. Tidak ada hard delete.
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");

                    if (preg_match('/^NSB-0*(\d+)$/i', $search, $matches)) {
                        $q->orWhere('id', (int) $matches[1]);
                    }
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request, CustomerService $customerService)
    {
        $data = $request->validated();

        $customer = $customerService->createCustomerAccount(
            $data,
            password: $data['password'],
        );

        $routePrefix = $request->user()->isAdmin() ? 'admin.nasabah' : 'petugas.nasabah';

        return redirect()
            ->route($routePrefix . '.show', $customer)
            ->with('success', 'Nasabah berhasil ditambahkan beserta akun loginnya.');
    }

    public function show(Customer $customer)
    {
        $customer->load('user');

        return view('customers.show', compact('customer'));
    }

    public function card(Request $request, Customer $customer)
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isPetugas(), 403);

        return view('customers.card', [
            'customer' => $customer,
            'pageTitle' => 'Cetak Kartu Nasabah',
            'backUrl' => route($request->user()->isAdmin() ? 'admin.nasabah.show' : 'petugas.nasabah.show', $customer),
        ]);
    }

    public function edit(Request $request, Customer $customer)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $customer->load('user');

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();

        DB::transaction(function () use ($customer, $data) {
            $customer->update([
                'name' => $data['name'],
                'address' => $data['address'],
                'phone' => $data['phone'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $customer->user?->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        });

        return redirect()
            ->route('admin.nasabah.show', $customer)
            ->with('success', 'Data nasabah berhasil diperbarui.');
    }

    /**
     * Nonaktifkan nasabah (idempoten: hanya mengubah menjadi nonaktif, tidak pernah mengaktifkan kembali).
     */
    public function deactivate(Request $request, Customer $customer)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($customer->is_active) {
            $customer->is_active = false;
            $customer->save();
        }

        return redirect()
            ->route('admin.nasabah.show', $customer)
            ->with('success', 'Nasabah berhasil dinonaktifkan.');
    }

    /**
     * Aktifkan kembali nasabah (khusus Admin, idempoten).
     */
    public function activate(Request $request, Customer $customer)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $customer->is_active) {
            $customer->is_active = true;
            $customer->save();
        }

        return redirect()
            ->route('admin.nasabah.show', $customer)
            ->with('success', 'Nasabah berhasil diaktifkan kembali.');
    }
}
