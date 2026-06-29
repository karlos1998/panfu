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
        $name = $this->nameFor($user);
        $email = $this->emailFor($user);
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

        $existingUser = $connection->table('users')->where('email', $email)->first();
        $identity = [
            'name' => $name,
            'password' => Hash::make($sessionKey),
            'current_gameserver' => 1,
            'tour_finished' => 1,
            'ticket_id' => $sessionKey,
            'updated_at' => $now,
        ];

        if ($existingUser) {
            $connection->table('users')->where('id', $existingUser->id)->update($identity);

            return;
        }

        $connection->table('users')->insert($identity + [
            'email' => $email,
            'sex' => 0,
            'coins' => (int) config('panfu.shop.default_coins', 1000),
            'goldpanda' => 1,
            'sheriff' => 0,
            'social_level' => 1,
            'social_score' => 0,
            'created_at' => $now,
        ]);
    }

    public function coinsFor(Authenticatable $user): ?int
    {
        $legacyUser = DB::connection('legacy_openpanfu')
            ->table('users')
            ->where('email', $this->emailFor($user))
            ->first(['coins']);

        return $legacyUser ? (int) $legacyUser->coins : null;
    }

    private function nameFor(Authenticatable $user): string
    {
        $name = method_exists($user, 'getAttribute') ? $user->getAttribute('name') : null;

        return (string) ($name ?: 'Panda'.$user->getAuthIdentifier());
    }

    private function emailFor(Authenticatable $user): string
    {
        $email = method_exists($user, 'getAttribute') ? $user->getAttribute('email') : null;

        return (string) ($email ?: 'player-'.$user->getAuthIdentifier().'@local.panfu');
    }
}
