<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\GoogleAuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteGoogleRegistrationRequest;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

/**
 * Controller tipis untuk autentikasi & pendaftaran mandiri via Akun Google — PBI-15.
 *
 * Menggunakan state OAuth bawaan Socialite (tanpa stateless()) dan meregenerasi
 * session setelah autentikasi berhasil.
 */
class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
    ) {
    }

    /**
     * Arahkan pengguna ke halaman persetujuan Google OAuth.
     */
    public function redirectToGoogle()
    {
        if (! $this->isGoogleConfigured()) {
            Log::warning('Konfigurasi Google OAuth belum lengkap pada server.');

            return redirect()->route('login')->withErrors([
                'email' => 'Fitur Masuk dengan Google belum dikonfigurasi pada server. Silakan masuk menggunakan email dan password.',
            ]);
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (Throwable $e) {
            Log::error('Gagal mengarahkan pengguna ke halaman Google OAuth.', [
                'exception' => $e::class,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Tidak dapat menghubungkan ke Google saat ini. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Tangani callback dari Google OAuth.
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            $oauthError = (string) $request->query('error');

            if ($oauthError === 'access_denied') {
                return redirect()->route('login')->withErrors([
                    'email' => 'Proses masuk dengan Google dibatalkan.',
                ]);
            }

            Log::warning('Callback Google OAuth mengembalikan parameter error.', [
                'oauth_error' => $oauthError,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Autentikasi Google tidak dapat dilanjutkan. Silakan coba lagi.',
            ]);
        }

        if (! $this->isGoogleConfigured()) {
            Log::warning('Callback Google OAuth dipanggil saat konfigurasi server belum lengkap.');

            return redirect()->route('login')->withErrors([
                'email' => 'Fitur Masuk dengan Google belum dikonfigurasi pada server.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            Log::warning('State OAuth Google tidak valid atau telah kedaluwarsa.');

            return redirect()->route('login')->withErrors([
                'email' => 'Sesi autentikasi Google tidak valid atau telah kedaluwarsa. Silakan coba lagi.',
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal memverifikasi respons pengguna dari Google OAuth.', [
                'exception' => $e::class,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Gagal terhubung ke layanan Google. Silakan coba lagi atau gunakan login email dan password.',
            ]);
        }

        try {
            $result = $this->googleAuthService->handleCallback($googleUser, $request->session());
        } catch (GoogleAuthException $e) {
            return redirect()->route('login')->withErrors([
                'email' => $e->getMessage(),
            ]);
        }

        if ($result['status'] === 'needs_registration') {
            return redirect()->route('google.register.form');
        }

        $user = $result['user'];
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($this->googleAuthService->dashboardRouteForRole($user->role));
    }

    /**
     * Tampilkan halaman Lengkapi Pendaftaran untuk email Google baru.
     */
    public function showCompleteRegistrationForm(Request $request)
    {
        try {
            $pending = $this->googleAuthService->getValidPendingRegistration($request->session());
        } catch (GoogleAuthException $e) {
            return redirect()->route('login')->withErrors([
                'email' => $e->getMessage(),
            ]);
        }

        return view('auth.google-complete', compact('pending'));
    }

    /**
     * Simpan pendaftaran Nasabah baru dan langsung autentikasi ke Portal Nasabah.
     */
    public function completeRegistration(CompleteGoogleRegistrationRequest $request)
    {
        try {
            $user = $this->googleAuthService->completeRegistration($request->validated(), $request->session());
        } catch (GoogleAuthException $e) {
            return redirect()->route('login')->withErrors([
                'email' => $e->getMessage(),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('nasabah.dashboard')
            ->with('success', 'Pendaftaran berhasil! Selamat datang di Portal Nasabah SetorKu.');
    }

    private function isGoogleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
