<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pengajuan penjemputan oleh Nasabah — PBI-07.
 *
 * Koordinat wajib diisi dan berada dalam rentang geografis Indonesia (lat -11..6, lng 95..141).
 * Tanggal pengajuan tidak boleh lampau. Status TIDAK dibaca dari browser.
 */
class StorePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isNasabah() && $user->customer !== null;
    }

    public function rules(): array
    {
        return [
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-11,6'],
            'longitude' => ['required', 'numeric', 'between:95,141'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'requested_date' => 'tanggal penjemputan',
            'address' => 'alamat penjemputan',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'notes' => 'catatan perkiraan sampah',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'date' => 'Format :attribute tidak valid.',
            'after_or_equal' => 'Tanggal penjemputan tidak boleh di masa lampau.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'numeric' => 'Kolom :attribute harus berupa angka koordinat.',
            'latitude.between' => 'Koordinat latitude harus berada di wilayah Indonesia (-11 s.d. 6).',
            'longitude.between' => 'Koordinat longitude harus berada di wilayah Indonesia (95 s.d. 141).',
        ];
    }
}
