<?php

namespace App\Services;

use App\Exceptions\GoogleAuthException;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

/**
 * Layanan bisnis Login & Pendaftaran Mandiri via Akun Google — PBI-15.
 *
 * Aturan keamanan:
 * - Pencocokan akun memakai email yang dinormalisasi (trim + huruf kecil).
 * - Wajib email_verified === true dari Google.
 * - Tidak pernah menyimpan access_token maupun refresh_token (hanya google_id).
 * - Akun nonaktif (nasabah/petugas/admin) ditolak seperti login biasa.
 * - Pendaftaran mandiri HANYA membuat akun User ber-role 'nasabah' + Customer bersaldo 0.
 * - role, email, google_id, is_active, dan balance selalu berasal dari server/session, bukan form.
 */
class GoogleAuthService
{
    public const PENDING_SESSION_KEY = 'google_oauth_pending_registration';

    public const PENDING_TTL_MINUTES = 15;

    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    /**
     * Proses data pengguna yang dikembalikan oleh callback Google OAuth.
     *
     * @return array{status: 'authenticated', user: User}|array{status: 'needs_registration'}
     *
     * @throws GoogleAuthException
     */
    public function handleCallback(SocialiteUserContract $googleUser, Session $session): array
    {
        if (! $this->isEmailVerified($googleUser)) {
            throw new GoogleAuthException('Email akun Google belum terverifikasi oleh Google.');
        }

        $googleId = trim((string) $googleUser->getId());
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $name = trim((string) ($googleUser->getName() ?? ''));

        if ($googleId === '' || $email === '') {
            throw new GoogleAuthException('Data akun Google tidak lengkap (email atau ID Google tidak tersedia).');
        }

        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if ($user !== null) {
            if ($user->google_id !== null && $user->google_id !== '' && ! hash_equals((string) $user->google_id, $googleId)) {
                throw new GoogleAuthException('Email ini sudah terhubung dengan akun Google yang berbeda.');
            }

            $googleIdUsedByOther = User::where('google_id', $googleId)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($googleIdUsedByOther) {
                throw new GoogleAuthException('Akun Google ini sudah terhubung dengan akun pengguna lain.');
            }

            if (! $user->isActiveAccount()) {
                throw new GoogleAuthException('Akun dinonaktifkan. Hubungi admin.');
            }

            $dirty = false;
            if ($user->google_id === null || $user->google_id === '') {
                $user->google_id = $googleId;
                $dirty = true;
            }
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }

            $session->forget(self::PENDING_SESSION_KEY);

            return [
                'status' => 'authenticated',
                'user' => $user,
            ];
        }

        if (User::where('google_id', $googleId)->exists()) {
            throw new GoogleAuthException('Akun Google ini sudah terhubung dengan akun pengguna lain.');
        }

        $session->put(self::PENDING_SESSION_KEY, [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $name,
            'expires_at' => now()->addMinutes(self::PENDING_TTL_MINUTES)->getTimestamp(),
        ]);

        return [
            'status' => 'needs_registration',
        ];
    }

    /**
     * Ambil data pendaftaran Google dari session server dan pastikan belum kedaluwarsa (maks 15 menit).
     *
     * @return array{google_id: string, email: string, name: string, expires_at: int}
     *
     * @throws GoogleAuthException
     */
    public function getValidPendingRegistration(Session $session): array
    {
        $pending = $session->get(self::PENDING_SESSION_KEY);

        if (
            ! is_array($pending)
            || empty($pending['google_id'])
            || empty($pending['email'])
            || empty($pending['expires_at'])
        ) {
            $session->forget(self::PENDING_SESSION_KEY);
            throw new GoogleAuthException('Sesi pendaftaran Google tidak ditemukan. Silakan klik Masuk dengan Google terlebih dahulu.');
        }

        if (now()->getTimestamp() > (int) $pending['expires_at']) {
            $session->forget(self::PENDING_SESSION_KEY);
            throw new GoogleAuthException('Sesi pendaftaran Google telah kedaluwarsa (lebih dari 15 menit). Silakan ulangi Masuk dengan Google.');
        }

        return [
            'google_id' => trim((string) $pending['google_id']),
            'email' => mb_strtolower(trim((string) $pending['email'])),
            'name' => trim((string) ($pending['name'] ?? '')),
            'expires_at' => (int) $pending['expires_at'],
        ];
    }

    /**
     * Selesaikan pendaftaran mandiri Nasabah baru dalam satu DB::transaction.
     *
     * @param  array{name: string, address: string, phone: string, latitude?: mixed, longitude?: mixed}  $validatedProfile
     *
     * @throws GoogleAuthException
     */
    public function completeRegistration(array $validatedProfile, Session $session): User
    {
        $pending = $this->getValidPendingRegistration($session);

        try {
            $customer = DB::transaction(function () use ($validatedProfile, $pending) {
                $emailAlreadyUsed = User::whereRaw('LOWER(TRIM(email)) = ?', [$pending['email']])
                    ->lockForUpdate()
                    ->exists();

                if ($emailAlreadyUsed) {
                    throw new GoogleAuthException('Email ini sudah terdaftar. Silakan langsung masuk menggunakan Google.');
                }

                $googleIdAlreadyUsed = User::where('google_id', $pending['google_id'])
                    ->lockForUpdate()
                    ->exists();

                if ($googleIdAlreadyUsed) {
                    throw new GoogleAuthException('Akun Google ini sudah terdaftar. Silakan langsung masuk menggunakan Google.');
                }

                return $this->customerService->createCustomerAccount(
                    data: [
                        'name' => $validatedProfile['name'],
                        'email' => $pending['email'],
                        'address' => $validatedProfile['address'],
                        'phone' => $validatedProfile['phone'],
                        'latitude' => $validatedProfile['latitude'] ?? null,
                        'longitude' => $validatedProfile['longitude'] ?? null,
                    ],
                    password: null,
                    googleId: $pending['google_id'],
                    markEmailVerified: true,
                );
            });
        } catch (GoogleAuthException $e) {
            $session->forget(self::PENDING_SESSION_KEY);
            throw $e;
        } catch (UniqueConstraintViolationException) {
            $session->forget(self::PENDING_SESSION_KEY);
            throw new GoogleAuthException('Email atau akun Google ini sudah terdaftar. Silakan masuk melalui halaman login.');
        }

        $session->forget(self::PENDING_SESSION_KEY);

        return $customer->user;
    }

    /**
     * Tentukan nama route dashboard sesuai role pengguna.
     */
    public function dashboardRouteForRole(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'petugas' => 'petugas.dashboard',
            'nasabah' => 'nasabah.dashboard',
            default => 'login',
        };
    }

    private function isEmailVerified(SocialiteUserContract $googleUser): bool
    {
        $raw = [];

        if (method_exists($googleUser, 'getRaw')) {
            $candidate = $googleUser->getRaw();
            if (is_array($candidate)) {
                $raw = $candidate;
            }
        }

        if ($raw === [] && isset($googleUser->user) && is_array($googleUser->user)) {
            $raw = $googleUser->user;
        }

        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return $verified === true;
    }
}
