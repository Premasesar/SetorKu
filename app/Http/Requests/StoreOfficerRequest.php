<?php

namespace App\Http\Requests;

/**
 * Validasi tambah akun Petugas baru oleh Admin — PBI-06b.
 */
class StoreOfficerRequest extends OfficerRequest
{
    public function rules(): array
    {
        return array_merge($this->officerProfileRules(), [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
