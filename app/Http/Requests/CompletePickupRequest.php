<?php

namespace App\Http\Requests;

use App\Models\PickupRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input hasil penjemputan oleh Petugas — PBI-09.
 *
 * - 1 sampai 10 baris
 * - Jenis sampah tidak boleh duplikat dalam satu penjemputan
 * - Berat tiap baris > 0
 * - Petugas lain yang bukan pemilik tugas langsung ditolak 403
 */
class CompletePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $pickup = $this->route('pickupRequest');

        return $user !== null
            && $user->isPetugas()
            && $pickup instanceof PickupRequest
            && (int) $pickup->officer_id === (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.waste_type_id' => ['required', 'integer', 'distinct', 'exists:waste_types,id'],
            'items.*.weight' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'daftar hasil timbangan',
            'items.*.waste_type_id' => 'jenis sampah',
            'items.*.weight' => 'berat sampah',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 baris hasil penjemputan.',
            'items.min' => 'Minimal harus ada 1 baris hasil penjemputan.',
            'items.max' => 'Maksimal hanya diperbolehkan 10 baris jenis sampah.',
            'items.*.waste_type_id.required' => 'Jenis sampah pada setiap baris wajib dipilih.',
            'items.*.waste_type_id.distinct' => 'Jenis sampah tidak boleh dobel dalam satu penjemputan.',
            'items.*.waste_type_id.exists' => 'Jenis sampah yang dipilih tidak ditemukan.',
            'items.*.weight.required' => 'Berat sampah pada setiap baris wajib diisi.',
            'items.*.weight.numeric' => 'Berat sampah harus berupa angka.',
            'items.*.weight.gt' => 'Berat sampah harus lebih besar dari 0.',
        ];
    }
}
