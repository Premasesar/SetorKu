<?php

namespace App\Http\Requests;

class StoreWasteTypeRequest extends WasteTypeRequest
{
    public function rules(): array
    {
        return $this->baseRules();
    }
}
