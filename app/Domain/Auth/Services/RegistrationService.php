<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\RegisterUserData;
use App\Domain\Auth\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class RegistrationService
{
    public function __construct(private readonly UserRepository $users) {}

    public function register(RegisterUserData $data): Authenticatable
    {
        $user = $this->users->create($data);

        event(new Registered($user));
        Auth::login($user);

        return $user;
    }
}
