<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\LegacyPlayerRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MySqlLegacyPlayerRepository implements LegacyPlayerRepository
{
    public function sync(Authenticatable $user, string $sessionKey): void
    {
        $name = method_exists($user, 'getAttribute') ? $user->getAttribute('name') : null;
        $email = method_exists($user, 'getAttribute') ? $user->getAttribute('email') : null;
        $now = now();
        $connection = DB::connection('legacy_openpanfu');

        if (! $connection->table('gameservers')->where('id', 1)->exists()) {
            $connection->table('gameservers')->insert([
                'id' => 1,
                'name' => 'OpenPanfu Local',
                'player_count' => 0,
                'url' => '127.0.0.1',
                'port' => 9595,
                'goldpanda' => 1,
                'secret_key' => 'local-development-secret',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $connection->table('gameservers')->where('id', 1)->update([
                'name' => 'OpenPanfu Local',
                'url' => '127.0.0.1',
                'port' => 9595,
                'goldpanda' => 1,
                'updated_at' => $now,
            ]);
        }

        $connection->table('users')->updateOrInsert(
            ['email' => $email ?: 'player-'.$user->getAuthIdentifier().'@local.panfu'],
            [
                'name' => $name ?: 'Panda'.$user->getAuthIdentifier(),
                'password' => Hash::make($sessionKey),
                'sex' => 0,
                'coins' => 1000,
                'goldpanda' => 1,
                'sheriff' => 0,
                'social_level' => 1,
                'social_score' => 0,
                'current_gameserver' => 1,
                'tour_finished' => 1,
                'ticket_id' => $sessionKey,
                'updated_at' => $now,
            ],
        );
    }
}
