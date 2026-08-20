<?php

namespace App\Domain\Servers;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GameServer;

final class GameServerService
{
    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    /** @return list<TypedObject> */
    public function available(): array
    {
        return GameServer::query()
            ->orderBy('id')
            ->get()
            ->map(fn (GameServer $server): TypedObject => $this->toValueObject($server))
            ->all();
    }

    public function selectedFor(int $serverId): ?TypedObject
    {
        $server = GameServer::query()->find($serverId) ?? GameServer::query()->orderBy('id')->first();

        return $server === null ? null : $this->toValueObject($server);
    }

    public function first(): ?GameServer
    {
        return GameServer::query()->orderBy('id')->first();
    }

    public function onlinePlayerCount(): int
    {
        return (int) GameServer::query()->sum('player_count');
    }

    private function toValueObject(GameServer $server): TypedObject
    {
        return $this->valueObjects->make('GameServer', [
            'id' => (int) $server->getKey(),
            'name' => (string) $server->name,
            'playercount' => (int) $server->player_count,
            'url' => (string) $server->url,
            'port' => (int) $server->port,
        ]);
    }
}
