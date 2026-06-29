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
