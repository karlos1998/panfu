<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PokoPet extends Model
{
    protected $table = 'pokopets';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'agility' => 'integer',
            'experience' => 'integer',
            'health' => 'integer',
            'last_fed' => 'datetime',
            'level' => 'integer',
            'max_health' => 'integer',
            'power' => 'integer',
            'selected' => 'boolean',
            'speed' => 'integer',
            'type' => 'integer',
            'x' => 'integer',
            'y' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
