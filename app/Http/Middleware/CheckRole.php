<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware RBAC untuk membatasi akses route berdasarkan role pengguna.
 *
 * Penggunaan di route:
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,petugas')
 *
 * Mendukung FR-001 (Autentikasi & Hak Akses) dan NFR-004 (Access Control).
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Pastikan pengguna sudah login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Periksa apakah role pengguna termasuk dalam daftar yang diizinkan
        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
