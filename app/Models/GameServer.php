<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameServer extends Model
{
    protected $table = 'gameservers';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'goldpanda' => 'boolean',
            'player_count' => 'integer',
            'port' => 'integer',
        ];
    }
}
