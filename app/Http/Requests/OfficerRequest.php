<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis validasi pengelolaan akun Petugas oleh Admin — PBI-06b.
 */
abstract class OfficerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge([
                'email' => mb_strtolower(trim($this->email)),
            ]);
        }
    }

    protected function officerProfileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama petugas',
            'email' => 'email',
            'phone' => 'nomor HP',
            'password' => 'password',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'min' => 'Kolom :attribute minimal :min karakter.',
            'email' => 'Format :attribute tidak valid.',
            'unique' => ':attribute sudah digunakan.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'regex' => 'Format :attribute tidak valid.',
        ];
    }
}
