<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorldEventContainer extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'value' => 'integer',
            'max_value' => 'integer',
        ];
    }
}
