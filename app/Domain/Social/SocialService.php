<?php

namespace App\Domain\Social;

use App\Application\Amf\ValueObjectFactory;
use App\Domain\Servers\GameServerClient;
use App\Enums\RelationType;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SocialService
{
    public function __construct(
        private readonly ValueObjectFactory $valueObjects,
        private readonly GameServerClient $gameServer,
    ) {}

    public function addFriends(User $player, User $buddy): void
    {
        DB::transaction(function () use ($player, $buddy): void {
            $this->setRelation($player, $buddy, RelationType::Friend);
            $this->setRelation($buddy, $player, RelationType::Friend);
        });
    }

    /** @return list<TypedObject> */
    public function smallFriendsFor(User $player): array
    {
        return $this->relatedPlayers($player, RelationType::Friend)
            ->map(fn (User $buddy): TypedObject => $this->valueObjects->make('SmallPlayerInfo', [
                'playerId' => (int) $buddy->getKey(),
                'playerName' => (string) $buddy->name,
                'currentGameServer' => (int) ($buddy->current_gameserver ?? 0),
            ]))
            ->all();
    }

    /** @return list<TypedObject> */
    public function friendsFor(User $player): array
    {
        return $this->relatedPlayers($player, RelationType::Friend)
            ->map(fn (User $buddy): TypedObject => $this->valueObjects->make('Buddy', [
                'id' => (int) $buddy->getKey(),
                'name' => (string) $buddy->name,
                'premium' => (int) $buddy->goldpanda,
                'bestfriend' => false,
                'currentGameServer' => (int) ($buddy->current_gameserver ?? 0),
                'socialLevel' => (int) $buddy->social_level,
            ]))
            ->all();
    }

    private function setRelation(User $owner, User $related, RelationType $type): void
    {
        UserRelation::query()->updateOrCreate(
            ['player1' => $owner->getKey(), 'player2' => $related->getKey()],
            ['relation_type' => $type],
        );
        $this->gameServer->send(
            'updateBuddyStatus',
            (int) $owner->getKey(),
            (int) $related->getKey(),
            $type->value,
        );
    }

    /** @return Collection<int, User> */
    private function relatedPlayers(User $player, RelationType $type)
    {
        $ids = UserRelation::query()
            ->where('player1', $player->getKey())
            ->where('relation_type', $type->value)
            ->pluck('player2');

        return User::query()->whereKey($ids)->get()->keyBy('id')->only($ids->all())->values();
    }
}
