<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'player_name', 'room_id', 'is_home', 'message', 'created_at'])]
class ChatMessage extends Model
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'room_id' => 'integer',
            'is_home' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
