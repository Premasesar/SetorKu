<?php

namespace App\Http\Requests;

class UpdateWasteTypeRequest extends WasteTypeRequest
{
    public function rules(): array
    {
        return $this->baseRules($this->route('wasteType')?->id);
    }
}
