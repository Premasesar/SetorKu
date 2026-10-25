<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Basis validasi tambah/ubah Jenis Sampah oleh Admin — PBI-03.
 */
abstract class WasteTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $unit = trim((string) $this->input('unit', ''));

        $this->merge([
            'unit' => $unit === '' ? 'kg' : $unit,
        ]);
    }

    protected function baseRules(?int $ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('waste_types', 'name')->ignore($ignoreId),
            ],
            'unit' => ['required', 'string', 'max:20'],
            'price_per_unit' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama jenis sampah',
            'unit' => 'satuan',
            'price_per_unit' => 'harga per satuan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max.',
            'unique' => ':attribute sudah terdaftar.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'gt' => 'Kolom :attribute harus lebih besar dari 0.',
        ];
    }
}
