<?php

namespace App\Domain\Panfu\Services;

use App\Models\User;

class PandaPlayercardService
{
    public function __construct(private readonly PandaPlayercardRenderer $renderer) {}

    public function forUsername(?string $username): string
    {
        $user = $username
            ? User::query()
                ->where('name', $username)
                ->with(['inventoryEntries' => fn ($query) => $query->where('active', true)->with('item')])
                ->first()
            : null;

        return $this->renderer->render($user);
    }
}
