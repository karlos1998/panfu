<?php

namespace App\Http\Requests\Admin;

use App\Domain\Admin\Data\InventoryData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'item_id' => [
                'required',
                'integer',
                'exists:items,id',
                Rule::unique('inventories', 'item_id')->where('user_id', $user->id),
            ],
            ...$this->inventoryRules(),
        ];
    }

    /** @return array<string, array<int, string>> */
    protected function inventoryRules(): array
    {
        return [
            'active' => ['required', 'boolean'],
            'bought' => ['required', 'boolean'],
            'x' => ['required', 'integer'],
            'y' => ['required', 'integer'],
            'rot' => ['required', 'integer'],
            'room' => ['required', 'integer', 'min:0'],
        ];
    }

    public function itemId(): int
    {
        return (int) $this->validated('item_id');
    }

    public function toData(): InventoryData
    {
        return new InventoryData(
            active: (bool) $this->validated('active'),
            bought: (bool) $this->validated('bought'),
            x: (int) $this->validated('x'),
            y: (int) $this->validated('y'),
            rotation: (int) $this->validated('rot'),
            room: (int) $this->validated('room'),
        );
    }
}
