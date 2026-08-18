<?php

namespace App\Domain\Admin\Data;

use App\Enums\UserRole;

readonly class AdminUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public UserRole $role,
        public bool $sex,
        public int $coins,
        public bool $goldPanda,
        public bool $sheriff,
        public int $socialLevel,
        public int $socialScore,
        public ?int $currentGameServer,
        public bool $tourFinished,
        public ?string $birthday,
        public bool $emailVerified,
        public ?string $password,
    ) {}
}
