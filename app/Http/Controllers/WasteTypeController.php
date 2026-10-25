<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWasteTypeRequest;
use App\Http\Requests\UpdateWasteTypeRequest;
use App\Models\WasteType;
use Illuminate\Http\Request;

/**
 * Pengelolaan Jenis Sampah & Harga — PBI-03 / FR-003.
 *
 * - Admin: daftar (semua), tambah, detail, ubah, nonaktifkan, aktifkan.
 * - Petugas: hanya melihat daftar jenis sampah yang aktif.
 */
class WasteTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $isAdmin = (bool) $request->user()?->isAdmin();

        $wasteTypes = WasteType::query()
            ->when(! $isAdmin, fn ($q) => $q->active())
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('waste-types.index', compact('wasteTypes', 'search'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('waste-types.create');
    }

    public function store(StoreWasteTypeRequest $request)
    {
        WasteType::create($request->validated());

        return redirect()
            ->route('admin.jenis-sampah.index')
            ->with('success', 'Jenis sampah berhasil ditambahkan.');
    }

    public function show(Request $request, WasteType $wasteType)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('waste-types.show', compact('wasteType'));
    }

    public function edit(Request $request, WasteType $wasteType)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('waste-types.edit', compact('wasteType'));
    }

    public function update(UpdateWasteTypeRequest $request, WasteType $wasteType)
    {
        $wasteType->update($request->validated());

        return redirect()
            ->route('admin.jenis-sampah.index')
            ->with('success', 'Jenis sampah berhasil diperbarui.');
    }

    public function deactivate(Request $request, WasteType $wasteType)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($wasteType->is_active) {
            $wasteType->is_active = false;
            $wasteType->save();
        }

        return redirect()
            ->route('admin.jenis-sampah.index')
            ->with('success', 'Jenis sampah berhasil dinonaktifkan.');
    }

    public function activate(Request $request, WasteType $wasteType)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $wasteType->is_active) {
            $wasteType->is_active = true;
            $wasteType->save();
        }

        return redirect()
            ->route('admin.jenis-sampah.index')
            ->with('success', 'Jenis sampah berhasil diaktifkan kembali.');
    }
}
