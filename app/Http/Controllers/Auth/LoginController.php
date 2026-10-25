<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller autentikasi login/logout.
 *
 * Mendukung:
 * - FR-001: Login dan pembatasan akses berdasarkan role
 * - UC-01: Melakukan Login
 * - AC US-01: Redirect sesuai role setelah login berhasil
 */
class LoginController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi login.
     *
     * Sequence Diagram Login:
     * 1. Terima kredensial dari form
     * 2. Validasi input
     * 3. Cek kredensial terhadap database
     * 4. Jika valid: baca role, redirect ke dashboard sesuai role
     * 5. Jika tidak valid: kembalikan pesan error, tetap di halaman login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! Auth::user()->isActiveAccount()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun dinonaktifkan. Hubungi admin.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return $this->redirectByRole(Auth::user()->role);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect ke dashboard berdasarkan role pengguna.
     */
    protected function redirectByRole(string $role)
    {
        return match ($role) {
            'admin'    => redirect()->route('admin.dashboard'),
            'petugas'  => redirect()->route('petugas.dashboard'),
            'nasabah'  => redirect()->route('nasabah.dashboard'),
            default    => redirect()->route('login'),
        };
    }
}
