<?php

namespace App\Models;

use App\Enums\RelationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRelation extends Model
{
    protected $table = 'relations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'relation_type' => RelationType::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1');
    }

    /** @return BelongsTo<User, $this> */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2');
    }
}
