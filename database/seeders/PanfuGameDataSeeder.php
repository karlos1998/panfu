<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PanfuGameDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $servers = $this->readJson('game-servers.json');

        foreach ($servers as &$server) {
            $server['created_at'] = $now;
            $server['updated_at'] = $now;
        }

        DB::table('gameservers')->upsert(
            $servers,
            ['id'],
            ['name', 'player_count', 'url', 'port', 'goldpanda', 'secret_key', 'updated_at'],
        );

        foreach (array_chunk($this->readJson('game-items.json'), 500) as $items) {
            DB::table('items')->upsert(
                $items,
                ['id'],
                ['name', 'type', 'price', 'z', 'premium'],
            );
        }

        $this->seedMinigameRewards($now);
    }

    private function seedMinigameRewards(\DateTimeInterface $now): void
    {
        $coinMultipliers = [
            11 => '0.0500', // Cloud Number Nine
            12 => '0.3333', // Cast Away
            24 => '0.1000', // Cool Cooking
            44 => '0.2500', // Parking
        ];
        $gameIds = collect(File::glob(public_path('vendor/openpanfu/swf/games/game*.swf')) ?: [])
            ->map(function (string $path): ?int {
                preg_match('/game(\d+)\.swf$/', $path, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        foreach ($gameIds->chunk(100) as $chunk) {
            DB::table('minigame_rewards')->insertOrIgnore(
                $chunk->map(fn (int $gameId): array => [
                    'game_id' => $gameId,
                    'coin_multiplier' => $coinMultipliers[$gameId] ?? '0.0500',
                    'max_coins_per_round' => null,
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $filename): array
    {
        $path = resource_path("data/panfu/{$filename}");

        return json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
