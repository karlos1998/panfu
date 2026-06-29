<?php

namespace App\Domain\Account\Services;

use App\Domain\Account\Data\AccountSettingsData;
use App\Domain\Account\Repositories\AccountRepository;
use App\Models\User;

class AccountSettingsService
{
    public function __construct(private readonly AccountRepository $accounts) {}

    /**
     * @return array<string, mixed>
     */
    public function settingsFor(User $user): array
    {
        return $this->accounts->settingsFor($user);
    }

    public function update(User $user, AccountSettingsData $data): void
    {
        $this->accounts->update($user, $data);
    }
}
