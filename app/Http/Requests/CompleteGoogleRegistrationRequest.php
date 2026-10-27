<?php

namespace App\Http\Requests;

use App\Exceptions\GoogleAuthException;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validasi form Lengkapi Pendaftaran Nasabah via Google — PBI-15.
 *
 * Hanya menerima nama, alamat, nomor HP, dan koordinat opsional.
 * Field sensitif seperti role, email, google_id, is_active, maupun balance
 * TIDAK ADA di aturan validasi sehingga selalu diabaikan jika dikirim dari browser.
 */
class CompleteGoogleRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        try {
            app(GoogleAuthService::class)->getValidPendingRegistration($this->session());
        } catch (GoogleAuthException $e) {
            throw new HttpResponseException(
                redirect()->route('login')->withErrors([
                    'email' => $e->getMessage(),
                ])
            );
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'address' => 'alamat lengkap',
            'phone' => 'nomor HP',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'between' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
            'required_with' => 'Kolom :attribute wajib diisi jika :values diisi.',
            'regex' => 'Format :attribute tidak valid.',
        ];
    }
}
