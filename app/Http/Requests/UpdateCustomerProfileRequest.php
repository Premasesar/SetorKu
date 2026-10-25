<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi ubah profil mandiri oleh Nasabah — PBI-06.
 *
 * Hanya mengizinkan perubahan nama, alamat, nomor HP, dan koordinat milik sendiri.
 * Field saldo (balance), kode, role, is_active, dan user_id TIDAK ada di aturan
 * sehingga selalu diabaikan bila dikirim dari browser.
 */
class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isNasabah() && $user->customer !== null;
    }

    public function rules(): array
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
