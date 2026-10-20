<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan Data Nasabah oleh Admin — PBI-02 / FR-002 / UC-02.
 *
 * Aksi: daftar (cari nama/alamat + paginasi), tambah, detail, ubah, nonaktifkan.
 * Tidak ada hard delete. Saldo (balance) tidak pernah diambil dari input form.
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

    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();

        // Akun User + profil Customer dibuat atomik: gagal salah satu, keduanya dibatalkan.
        $customer = DB::transaction(function () use ($data) {
            // Password otomatis di-hash oleh cast 'hashed' pada model User
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'nasabah',
            ]);

            return $user->customer()->create([
                'name' => $data['name'],
                'address' => $data['address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.nasabah.show', $customer)
            ->with('success', 'Nasabah berhasil ditambahkan beserta akun loginnya.');
    }

    public function show(Customer $customer)
    {
        $customer->load('user');

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
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
     * Nonaktifkan nasabah: is_active = false, data tidak dihapus.
     */
    public function deactivate(Customer $customer)
    {
        $customer->is_active = ! $customer->is_active;
        $customer->save();

        $message = $customer->is_active
            ? 'Nasabah berhasil diaktifkan kembali.'
            : 'Nasabah berhasil dinonaktifkan.';

        return redirect()
            ->route('admin.nasabah.show', $customer)
            ->with('success', $message);
    }
}
