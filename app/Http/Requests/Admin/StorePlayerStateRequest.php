<?php

namespace App\Http\Requests\Admin;

use App\Domain\Admin\Data\PlayerStateData;
use Illuminate\Foundation\Http\FormRequest;

class StorePlayerStateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['required', 'integer', 'min:0'],
            'name' => ['required', 'integer', 'min:0'],
            'value' => ['required', 'integer'],
        ];
    }

    public function toData(): PlayerStateData
    {
        return new PlayerStateData(
            category: (int) $this->validated('category'),
            name: (int) $this->validated('name'),
            value: (int) $this->validated('value'),
        );
    }
}
