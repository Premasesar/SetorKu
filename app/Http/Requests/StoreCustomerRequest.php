<?php

namespace App\Http\Requests;

/**
 * Validasi tambah nasabah baru oleh Admin.
 * Sekaligus membuat akun User berperan Nasabah (email + password).
 */
class StoreCustomerRequest extends CustomerRequest
{
    public function rules(): array
    {
        return array_merge($this->profileRules(), [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
