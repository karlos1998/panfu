<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerState extends Model
{
    protected $table = 'states';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => 'integer',
            'last_changed' => 'integer',
            'name' => 'integer',
            'value' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
