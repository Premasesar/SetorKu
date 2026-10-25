<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RouteOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Halaman Admin "Rute Penjemputan" (Hitung & Simpan Urutan Rute) — PBI-11.
 */
class AdminRouteController extends Controller
{
    public function __construct(private readonly RouteOptimizationService $routeService)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $officers = User::activeOfficers()->orderBy('name')->get();
        $selectedDate = (string) $request->query('date', now()->toDateString());
        $selectedOfficerId = $request->filled('officer_id') ? $request->integer('officer_id') : null;

        $selectedOfficer = null;
        $calculation = null;

        if ($selectedOfficerId !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedOfficer = User::where('role', 'petugas')->whereKey($selectedOfficerId)->first();
            if ($selectedOfficer !== null) {
                $calculation = $this->routeService->calculateForOfficerAndDate(
                    officerId: $selectedOfficer->id,
                    date: $selectedDate,
                );
            }
        }

        return view('routes.admin', [
            'officers' => $officers,
            'selectedDate' => $selectedDate,
            'selectedOfficerId' => $selectedOfficerId,
            'selectedOfficer' => $selectedOfficer,
            'calculation' => $calculation,
            'depot' => $this->routeService->depotConfig(),
        ]);
    }

    public function save(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'officer_id' => ['required', 'integer'],
        ], [
            'date.required' => 'Tanggal jadwal wajib dipilih.',
            'date.date_format' => 'Format tanggal tidak valid.',
            'officer_id.required' => 'Petugas wajib dipilih.',
        ]);

        $officer = User::activeOfficers()->whereKey((int) $validated['officer_id'])->first();
        if ($officer === null) {
            throw ValidationException::withMessages([
                'officer_id' => 'Petugas tidak valid atau sedang nonaktif.',
            ]);
        }

        $result = $this->routeService->saveForOfficerAndDate(
            officerId: $officer->id,
            date: $validated['date'],
            admin: $request->user(),
        );

        return redirect()
            ->route('admin.rute.index', [
                'date' => $validated['date'],
                'officer_id' => $officer->id,
            ])
            ->with('success', "Urutan rute penjemputan berhasil disimpan ({$result['valid_count']} titik diurutkan).");
    }
}
