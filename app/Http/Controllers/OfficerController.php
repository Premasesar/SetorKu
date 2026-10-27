<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetOfficerPasswordRequest;
use App\Http\Requests\StoreOfficerRequest;
use App\Http\Requests\UpdateOfficerRequest;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Pengelolaan Akun Petugas oleh Admin — PBI-06b.
 *
 * Hanya beroperasi pada akun User ber-role 'petugas'.
 * Tidak boleh menampilkan atau mengubah akun 'admin' maupun 'nasabah'.
 * Tidak ada hard delete.
 */
class OfficerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $search = trim((string) $request->query('q', ''));

        $officers = User::officers()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");

                    if (preg_match('/^PTG-0*(\d+)$/i', $search, $m)) {
                        $q->orWhere('id', (int) $m[1]);
                    }
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('officers.index', compact('officers', 'search'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('officers.create');
    }

    public function store(StoreOfficerRequest $request)
    {
        $data = $request->validated();
        $password = $data['password'] ?? null;

        $officer = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'password' => ($password !== null && $password !== '') ? $password : null,
            'role' => 'petugas',
        ]);

        return redirect()
            ->route('admin.petugas.show', $officer)
            ->with('success', 'Akun petugas berhasil ditambahkan.');
    }

    public function show(Request $request, User $officer)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $this->ensureOfficerAccount($officer);

        return view('officers.show', compact('officer'));
    }

    public function edit(Request $request, User $officer)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $this->ensureOfficerAccount($officer);

        return view('officers.edit', compact('officer'));
    }

    public function update(UpdateOfficerRequest $request, User $officer)
    {
        $this->ensureOfficerAccount($officer);
        $data = $request->validated();

        $officer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()
            ->route('admin.petugas.show', $officer)
            ->with('success', 'Data petugas berhasil diperbarui.');
    }

    public function resetPassword(ResetOfficerPasswordRequest $request, User $officer)
    {
        $this->ensureOfficerAccount($officer);

        $officer->password = $request->validated('password');
        $officer->save();

        return redirect()
            ->route('admin.petugas.show', $officer)
            ->with('success', 'Password petugas berhasil direset.');
    }

    /**
     * Nonaktifkan akun petugas (idempoten: hanya menonaktifkan, tidak pernah mengaktifkan kembali).
     */
    public function deactivate(Request $request, User $officer)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $this->ensureOfficerAccount($officer);

        if ($officer->is_active) {
            $officer->is_active = false;
            $officer->save();
        }

        return redirect()
            ->route('admin.petugas.show', $officer)
            ->with('success', 'Akun petugas berhasil dinonaktifkan.');
    }

    /**
     * Aktifkan kembali akun petugas (idempoten).
     */
    public function activate(Request $request, User $officer)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $this->ensureOfficerAccount($officer);

        if (! $officer->is_active) {
            $officer->is_active = true;
            $officer->save();
        }

        return redirect()
            ->route('admin.petugas.show', $officer)
            ->with('success', 'Akun petugas berhasil diaktifkan kembali.');
    }

    private function ensureOfficerAccount(User $officer): void
    {
        abort_unless($officer->isPetugas(), 403, 'Menu kelola petugas hanya berlaku untuk akun ber-role petugas.');
    }
}
