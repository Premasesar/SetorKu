<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input pencatatan setoran oleh Petugas — PBI-04.
 *
 * Harga, total, saldo, dan officer_id TIDAK pernah dibaca dari request.
 */
class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isPetugas();
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'waste_type_id' => ['required', 'integer', 'exists:waste_types,id'],
            'weight' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'nasabah',
            'waste_type_id' => 'jenis sampah',
            'weight' => 'berat sampah',
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
