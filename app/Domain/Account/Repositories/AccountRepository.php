<?php

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Data\AccountSettingsData;
use App\Models\User;

interface AccountRepository
{
    /**
     * @return array<string, mixed>
     */
    public function settingsFor(User $user): array;

    public function update(User $user, AccountSettingsData $data): void;
}
