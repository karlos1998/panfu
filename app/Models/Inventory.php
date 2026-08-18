<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventories';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'bought' => 'boolean',
            'item_id' => 'integer',
            'room' => 'integer',
            'rot' => 'integer',
            'x' => 'integer',
            'y' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
