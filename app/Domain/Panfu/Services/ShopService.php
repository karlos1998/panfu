<?php

namespace App\Domain\Panfu\Services;

use App\Domain\Panfu\Repositories\LegacyPlayerRepository;
use App\Domain\Panfu\Repositories\ShopRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

class ShopService
{
    public function __construct(
        private readonly ShopRepository $shops,
        private readonly LegacyPlayerRepository $legacyPlayers,
    ) {}

    /**
     * @return array{coins: int, items: array<string, mixed>}
     */
    public function getCatalogueFor(?Authenticatable $user): array
    {
        $catalogue = $this->shops->getCatalogue();

        return [
            'coins' => $this->coinsFor($user),
            'items' => $catalogue['items'] ?? [],
        ];
    }

    private function coinsFor(?Authenticatable $user): int
    {
        $fallback = (int) config('panfu.shop.default_coins', 1000);

        if ($user === null || ! config('panfu.game_client.sync_legacy_player')) {
            return $fallback;
        }

        try {
            return $this->legacyPlayers->coinsFor($user) ?? $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }
}
