<?php

namespace App\Domain\Panfu\Services;

use App\Models\User;

class PandaAvatarService
{
    /** @return array{url: string} */
    public function forUser(?User $user): array
    {
        return [
            'url' => route('panfu.playercard', array_filter([
                'user' => $user?->name,
            ]), absolute: false),
        ];
    }
}
