<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinigameReward extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'coin_multiplier' => 'decimal:4',
            'enabled' => 'boolean',
            'game_id' => 'integer',
            'max_coins_per_round' => 'integer',
        ];
    }
}
