<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinboardMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['read' => 'boolean', 'deleted' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
