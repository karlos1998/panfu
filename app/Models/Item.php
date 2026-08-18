<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'premium' => 'boolean',
            'price' => 'integer',
            'type' => 'integer',
            'z' => 'integer',
        ];
    }

    /** @return HasMany<Inventory, $this> */
    public function inventoryEntries(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
