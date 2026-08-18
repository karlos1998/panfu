<?php

namespace App\Http\Requests\Admin;

class UpdateInventoryRequest extends StoreInventoryRequest
{
    public function rules(): array
    {
        return $this->inventoryRules();
    }
}
