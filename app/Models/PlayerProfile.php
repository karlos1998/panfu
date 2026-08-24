<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $guarded = [];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'last_blocked' => 'integer',
            'movie_checked' => 'boolean',
            'color_checked' => 'boolean',
            'hobby_checked' => 'boolean',
            'book_checked' => 'boolean',
            'song_checked' => 'boolean',
            'band_checked' => 'boolean',
            'school_subject_checked' => 'boolean',
            'sport_checked' => 'boolean',
            'animal_checked' => 'boolean',
            'rel_status_checked' => 'boolean',
            'motto_checked' => 'boolean',
            'best_char_checked' => 'boolean',
            'worst_char_checked' => 'boolean',
            'like_most_checked' => 'boolean',
            'like_least_checked' => 'boolean',
        ];
    }
}
