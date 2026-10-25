<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan sesi aktif milik akun yang dinonaktifkan (nasabah maupun petugas)
 * langsung logout otomatis pada request berikutnya.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $currentUser = $user->fresh() ?? $user;

            if (! $currentUser->isActiveAccount()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Akun dinonaktifkan. Hubungi admin.',
                ]);
            }
        }

        return $next($request);
    }
}
