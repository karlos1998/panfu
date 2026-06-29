<?php

namespace App\Infrastructure\Account\Repositories;

use App\Domain\Account\Data\AccountSettingsData;
use App\Domain\Account\Repositories\AccountRepository;
use App\Models\User;

class EloquentAccountRepository implements AccountRepository
{
    /**
     * @return array<string, mixed>
     */
    public function settingsFor(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->sex ? 'girl' : 'boy',
            'coins' => $user->coins,
            'goldPanda' => (bool) $user->goldpanda,
            'socialLevel' => $user->social_level,
            'socialScore' => $user->social_score,
            'createdAt' => $user->created_at?->toDateString(),
            'lastLogin' => $user->last_login?->toDateString(),
        ];
    }

    public function update(User $user, AccountSettingsData $data): void
    {
        $user->forceFill([
            'name' => $data->name,
            'email' => $data->email,
            'sex' => $data->sex,
        ]);

        if ($data->password !== null) {
            $user->forceFill(['password' => $data->password]);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
