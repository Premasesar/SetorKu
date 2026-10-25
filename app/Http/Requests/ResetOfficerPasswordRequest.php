<?php

namespace App\Http\Requests;

use App\Models\User;

/**
 * Validasi reset password akun Petugas oleh Admin — PBI-06b.
 */
class ResetOfficerPasswordRequest extends OfficerRequest
{
    public function authorize(): bool
    {
        $officer = $this->route('officer');

        return parent::authorize()
            && $officer instanceof User
            && $officer->isPetugas();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
