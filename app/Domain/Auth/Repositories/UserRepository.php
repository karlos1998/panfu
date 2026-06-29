<?php

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Data\RegisterUserData;
use Illuminate\Contracts\Auth\Authenticatable;

interface UserRepository
{
    public function create(RegisterUserData $data): Authenticatable;
}
