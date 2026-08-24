<?php

namespace App\Domain\Player;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\PlayerState;
use App\Models\User;

final class PlayerStateService
{
    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    /** @param list<int> $categories @return list<TypedObject> */
    public function get(User $player, array $categories): array
    {
        return $player->states()
            ->whereIn('category', array_map('intval', $categories))
            ->get()
            ->map(fn (PlayerState $state): TypedObject => $this->toValueObject($state))
            ->all();
    }

    /** @return list<TypedObject> */
    public function getProfileRange(User $player, int $firstCategory, int $lastCategory, int $name): array
    {
        [$firstCategory, $lastCategory] = $firstCategory <= $lastCategory
            ? [$firstCategory, $lastCategory]
            : [$lastCategory, $firstCategory];

        return $player->states()
            ->whereBetween('category', [$firstCategory, $lastCategory])
            ->where('name', $name)
            ->orderBy('category')
            ->get()
            ->map(fn (PlayerState $state): TypedObject => $this->toValueObject($state))
            ->all();
    }

    public function set(User $player, int $category, int $name, int $value): TypedObject
    {
        $timestamp = time();
        $state = $player->states()->updateOrCreate(
            ['category' => $category, 'name' => $name],
            ['value' => $value, 'last_changed' => $timestamp],
        );

        return $this->toValueObject($state);
    }

    private function toValueObject(PlayerState $state): TypedObject
    {
        return $this->valueObjects->make('State', [
            'playerId' => (int) $state->user_id,
            'cathegoryId' => (int) $state->category,
            'nameId' => (int) $state->name,
            'stateValue' => (int) $state->value,
            'lastChanged' => (int) $state->last_changed * 100000000,
        ]);
    }
}
