<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis validasi data nasabah: aturan bersama, label atribut, dan pesan
 * berbahasa Indonesia. Field 'balance' sengaja tidak ada di aturan sehingga
 * nilai saldo dari form selalu diabaikan.
 */
abstract class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Lapisan kedua setelah middleware role:admin
        return (bool) $this->user()?->isAdmin();
    }

    protected function profileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'address' => 'alamat',
            'phone' => 'nomor HP',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'email' => 'email',
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
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'between' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'required_with' => 'Kolom :attribute wajib diisi jika :values diisi.',
            'regex' => 'Format :attribute tidak valid.',
        ];
    }
}
