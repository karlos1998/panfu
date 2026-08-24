<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPackageCode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
            'redeemed_by' => 'integer',
        ];
    }
}
