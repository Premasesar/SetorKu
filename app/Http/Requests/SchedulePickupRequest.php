<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi penjadwalan dan ubah jadwal penjemputan oleh Admin — PBI-08.
 */
class SchedulePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'officer_id' => ['required', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'scheduled_date' => 'tanggal jadwal penjemputan',
            'officer_id' => 'petugas penjemput',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'date' => 'Format :attribute tidak valid.',
            'after_or_equal' => 'Tanggal jadwal penjemputan tidak boleh sebelum hari ini.',
            'integer' => 'Pilihan :attribute tidak valid.',
        ];
    }
}
