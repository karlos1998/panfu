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
        $this->gameServer->send(
            'updateBuddyStatus',
            (int) $player->getKey(),
            (int) $buddy->getKey(),
            RelationType::Friend->value,
        );
        $this->gameServer->send(
            'updateBuddyStatus',
            (int) $buddy->getKey(),
            (int) $player->getKey(),
            RelationType::Friend->value,
        );
    }

    public function removeFriends(User $player, User $buddy): void
    {
        DB::transaction(function () use ($player, $buddy): void {
            UserRelation::query()
                ->where(fn ($query) => $query
                    ->where(['player1' => $player->getKey(), 'player2' => $buddy->getKey()])
                    ->orWhere(['player1' => $buddy->getKey(), 'player2' => $player->getKey()]))
                ->where('relation_type', RelationType::Friend->value)
                ->delete();

            User::query()
                ->whereIn('id', [$player->getKey(), $buddy->getKey()])
                ->whereIn('best_friend_id', [$player->getKey(), $buddy->getKey()])
                ->update(['best_friend_id' => null]);
        });
    }

    public function block(User $player, User $blocked, int $level = 1): UserRelation
    {
        $this->removeFriends($player, $blocked);

        return UserRelation::query()->updateOrCreate(
            ['player1' => $player->getKey(), 'player2' => $blocked->getKey()],
            ['relation_type' => RelationType::Blocked],
        );
    }

    public function unblock(User $player, User $blocked): bool
    {
        return UserRelation::query()
            ->where('player1', $player->getKey())
            ->where('player2', $blocked->getKey())
            ->where('relation_type', RelationType::Blocked->value)
            ->delete() > 0;
    }

    /** @return list<TypedObject> */
    public function blockedRelationsFor(User $player): array
    {
        return UserRelation::query()
            ->where('relation_type', RelationType::Blocked->value)
            ->where(fn ($query) => $query
                ->where('player1', $player->getKey())
                ->orWhere('player2', $player->getKey()))
            ->orderBy('id')
            ->get()
            ->map(fn (UserRelation $relation): TypedObject => $this->valueObjects->make('BuddyFilter', [
                'buddy1' => (int) $relation->player1,
                'buddy2' => (int) $relation->player2,
                'level' => 1,
            ]))
            ->all();
    }

    public function changeBestFriend(User $player, int $oldBuddyId, int $newBuddyId): bool
    {
        if ($newBuddyId <= 0) {
            $player->forceFill(['best_friend_id' => null])->save();

            return true;
        }

        $isFriend = UserRelation::query()
            ->where('player1', $player->getKey())
            ->where('player2', $newBuddyId)
            ->where('relation_type', RelationType::Friend->value)
            ->exists();
        if (! $isFriend) {
            return false;
        }

        $player->forceFill(['best_friend_id' => $newBuddyId])->save();

        return true;
    }

    /** @param list<int> $ids @return list<TypedObject> */
    public function buddiesByIds(User $player, array $ids): array
    {
        $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, 100);

        return User::query()->whereKey($ids)->get()->keyBy('id')->only($ids)->values()
            ->map(fn (User $buddy): TypedObject => $this->buddyValueObject($player, $buddy))
            ->all();
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
            ->map(fn (User $buddy): TypedObject => $this->buddyValueObject($player, $buddy))
            ->all();
    }

    private function buddyValueObject(User $player, User $buddy): TypedObject
    {
        return $this->valueObjects->make('Buddy', [
            'id' => (int) $buddy->getKey(),
            'name' => (string) $buddy->name,
            'premium' => (int) $buddy->goldpanda > 0,
            'bestfriend' => (int) ($player->best_friend_id ?? 0) === (int) $buddy->getKey(),
            'currentGameServer' => (int) ($buddy->current_gameserver ?? 0),
            'socialLevel' => (int) $buddy->social_level,
        ]);
    }

    private function setRelation(User $owner, User $related, RelationType $type): void
    {
        UserRelation::query()->updateOrCreate(
            ['player1' => $owner->getKey(), 'player2' => $related->getKey()],
            ['relation_type' => $type],
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
