<?php

namespace App\Domain\Panfu\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;

interface LegacyPlayerRepository
{
    public function sync(Authenticatable $user, string $sessionKey): void;
}
