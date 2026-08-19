<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameHighScore extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'game_id' => 'integer',
            'score' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
