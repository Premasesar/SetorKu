<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input pencatatan penarikan saldo oleh Petugas — PBI-05.
 *
 * Saldo nasabah dan officer_id TIDAK pernah dibaca dari request.
 */
class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isPetugas();
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'nasabah',
            'amount' => 'nominal penarikan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'integer' => 'Pilihan :attribute tidak valid.',
            'exists' => ':attribute yang dipilih tidak ditemukan.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'gt' => 'Kolom :attribute harus lebih besar dari 0.',
            'max' => 'Kolom :attribute melebihi batas maksimum.',
        ];
    }
}
