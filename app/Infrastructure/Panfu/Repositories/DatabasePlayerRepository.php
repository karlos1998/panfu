<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\PlayerRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class DatabasePlayerRepository implements PlayerRepository
{
    public function syncForFlashSession(Authenticatable $user, string $sessionKey): void
    {
        DB::transaction(function () use ($user, $sessionKey): void {
            $this->ensureGameServer();

            $player = DB::table('users')
                ->where('id', $user->getAuthIdentifier())
                ->lockForUpdate()
                ->first([
                    'coins',
                    'goldpanda',
                    'sheriff',
                    'social_level',
                    'social_score',
                    'sex',
                ]);

            if ($player === null) {
                return;
            }

            DB::table('users')
                ->where('id', $user->getAuthIdentifier())
                ->update([
                    'coins' => $player->coins ?? (int) config('panfu.shop.default_coins', 1000),
                    'goldpanda' => $player->goldpanda ?? 1,
                    'sheriff' => $player->sheriff ?? false,
                    'social_level' => $player->social_level ?? 1,
                    'social_score' => $player->social_score ?? 0,
                    'sex' => $player->sex ?? false,
                    'current_gameserver' => 1,
                    'tour_finished' => true,
                    'ticket_id' => $sessionKey,
                    'last_login' => now()->toDateString(),
                    'updated_at' => now(),
                ]);

            $this->ensureStarterInventory((int) $user->getAuthIdentifier());
        });
    }

    public function coinsFor(Authenticatable $user): ?int
    {
        $coins = DB::table('users')
            ->where('id', $user->getAuthIdentifier())
            ->value('coins');

        return $coins === null ? null : (int) $coins;
    }

    private function ensureGameServer(): void
    {
        $now = now();

        DB::table('gameservers')->upsert([
            [
                'id' => 1,
                'name' => config('panfu.game_client.server_name', 'Local Panfu'),
                'player_count' => 0,
                'url' => config('panfu.game_server.host', '127.0.0.1'),
                'port' => (int) config('panfu.game_server.port', 9595),
                'goldpanda' => true,
                'secret_key' => config('panfu.game_server.secret_key', 'local-development-secret'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id'], ['name', 'url', 'port', 'goldpanda', 'secret_key', 'updated_at']);
    }

    private function ensureStarterInventory(int $userId): void
    {
        $now = now();

        foreach (config('panfu.player.starter_inventory', []) as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);

            if ($itemId <= 0 || $this->hasInventoryItem($userId, $itemId)) {
                continue;
            }

            DB::table('inventories')->insert([
                'user_id' => $userId,
                'active' => (bool) ($item['active'] ?? false),
                'bought' => true,
                'item_id' => $itemId,
                'x' => 0,
                'y' => 0,
                'rot' => 0,
                'room' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function hasInventoryItem(int $userId, int $itemId): bool
    {
        return DB::table('inventories')
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->exists();
    }
}
