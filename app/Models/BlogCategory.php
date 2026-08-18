<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $guarded = [];

    /** @return HasMany<BlogPost, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
