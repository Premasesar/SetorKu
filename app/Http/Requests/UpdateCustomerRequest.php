<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Validasi ubah data nasabah oleh Admin.
 * Email harus unik, kecuali milik akun nasabah itu sendiri.
 */
class UpdateCustomerRequest extends CustomerRequest
{
    public function rules(): array
    {
        $userId = $this->route('customer')?->user_id;

        return array_merge($this->profileRules(), [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
        ]);
    }
}
