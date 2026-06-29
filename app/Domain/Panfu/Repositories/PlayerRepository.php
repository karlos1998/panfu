<?php

namespace App\Domain\Panfu\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;

interface PlayerRepository
{
    public function syncForFlashSession(Authenticatable $user, string $sessionKey): void;

    public function coinsFor(Authenticatable $user): ?int;
}
