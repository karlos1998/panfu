<?php

namespace App\Application\Amf;

use App\Models\User;
use Illuminate\Contracts\Session\Session;

final class PlayerSession
{
    private const PLAYER_KEY = 'amf.player_id';

    public function __construct(private readonly Session $session) {}

    public function login(User $player): void
    {
        $this->session->migrate(true);
        $this->session->put(self::PLAYER_KEY, $player->getKey());
    }

    public function logout(): void
    {
        $this->session->forget(self::PLAYER_KEY);
    }

    public function player(): ?User
    {
        $id = $this->session->get(self::PLAYER_KEY);
        if (! is_numeric($id)) {
            return null;
        }

        $player = User::query()->find((int) $id);
        if ($player === null) {
            $this->logout();
        }

        return $player;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get("amf.{$key}", $default);
    }

    public function put(string $key, mixed $value): void
    {
        $this->session->put("amf.{$key}", $value);
    }

    public function forget(string $key): void
    {
        $this->session->forget("amf.{$key}");
    }
}
