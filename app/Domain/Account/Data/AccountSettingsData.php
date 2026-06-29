<?php

namespace App\Domain\Account\Data;

readonly class AccountSettingsData
{
    public function __construct(
        public string $name,
        public string $email,
        public bool $sex,
        public ?string $password,
    ) {}
}
