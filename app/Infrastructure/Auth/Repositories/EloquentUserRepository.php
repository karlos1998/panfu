<?php

namespace App\Infrastructure\Auth\Repositories;

use App\Domain\Auth\Data\RegisterUserData;
use App\Domain\Auth\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class EloquentUserRepository implements UserRepository
{
    public function create(RegisterUserData $data): Authenticatable
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }
}
