<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Validasi ubah profil Petugas (nama, email, no HP) oleh Admin — PBI-06b.
 * Menolak perubahan apabila target bukan akun ber-role petugas.
 */
class UpdateOfficerRequest extends OfficerRequest
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
        /** @var User $officer */
        $officer = $this->route('officer');

        return array_merge($this->officerProfileRules(), [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($officer->id)],
        ]);
    }
}
