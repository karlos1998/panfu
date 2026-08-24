<?php

namespace App\Domain\Pets;

use App\Application\Amf\ValueObjectFactory;
use App\Models\PokoPet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use stdClass;
use Throwable;

final class PetService
{
    /** @var array<int, array{name:string,price:int,level:int,premium:bool,voucher:bool}> */
    private const DEFINITIONS = [
        1 => ['name' => 'Helmet', 'price' => 4500, 'level' => 0, 'premium' => true, 'voucher' => false],
        2 => ['name' => 'Stella', 'price' => 8000, 'level' => 0, 'premium' => true, 'voucher' => false],
        3 => ['name' => 'Soque', 'price' => 1200, 'level' => 20, 'premium' => true, 'voucher' => false],
        4 => ['name' => 'Cuddle', 'price' => 7500, 'level' => 20, 'premium' => true, 'voucher' => false],
        5 => ['name' => 'Woody', 'price' => 0, 'level' => 0, 'premium' => false, 'voucher' => true],
        6 => ['name' => 'Bugsy', 'price' => 60, 'level' => 1, 'premium' => true, 'voucher' => false],
        7 => ['name' => 'Tork', 'price' => 5500, 'level' => 25, 'premium' => true, 'voucher' => false],
        8 => ['name' => 'Pingoo', 'price' => 0, 'level' => 0, 'premium' => false, 'voucher' => false],
        9 => ['name' => 'Marieta', 'price' => 60, 'level' => 0, 'premium' => false, 'voucher' => false],
    ];

    private const ALLOWED_STATES = [
        'normal', 'idle', 'sleeping', 'playing', 'eating', 'walking',
        'denying', 'decrease', 'rescue', 'tricking',
    ];

    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    /** @return list<stdClass> */
    public function forPlayer(User $player): array
    {
        return $player->pokoPets()
            ->orderByDesc('selected')
            ->orderBy('id')
            ->get()
            ->map(fn (PokoPet $pet): stdClass => $this->toValueObject($pet))
            ->all();
    }

    /** @return list<int> */
    public function withoutHealth(User $player): array
    {
        return $player->pokoPets()
            ->where('health', '<=', 0)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /** @return array{statusCode:int,message:string,pet:?stdClass} */
    public function buy(User $player, int $type, string $name, bool $voucherReserved): array
    {
        $definition = self::DEFINITIONS[$type] ?? null;
        if ($definition === null) {
            return ['statusCode' => 3, 'message' => 'Unknown Pokopet type.', 'pet' => null];
        }

        try {
            return DB::transaction(function () use ($player, $type, $name, $voucherReserved, $definition): array {
                $lockedPlayer = User::query()->lockForUpdate()->find($player->getKey());
                if ($lockedPlayer === null) {
                    return ['statusCode' => 1, 'message' => 'Player not found.', 'pet' => null];
                }
                if ($lockedPlayer->pokoPets()->where('type', $type)->lockForUpdate()->exists()) {
                    return ['statusCode' => 10, 'message' => 'Pokopet already owned.', 'pet' => null];
                }
                if ($definition['premium'] && (int) $lockedPlayer->goldpanda <= 0) {
                    return ['statusCode' => 5, 'message' => 'A Gold Panda membership is required.', 'pet' => null];
                }
                if ((int) $lockedPlayer->social_level < $definition['level']) {
                    return ['statusCode' => 6, 'message' => 'The required Panda level has not been reached.', 'pet' => null];
                }

                if ($definition['voucher']) {
                    $voucher = $lockedPlayer->inventoryEntries()->where('item_id', 101830)->lockForUpdate()->first();
                    if (! $voucherReserved || $voucher === null) {
                        return ['statusCode' => 13, 'message' => 'A valid Pokopet voucher is required.', 'pet' => null];
                    }
                    $voucher->delete();
                } elseif ((int) $lockedPlayer->coins < $definition['price']) {
                    return ['statusCode' => 412, 'message' => 'Not enough coins.', 'pet' => null];
                } elseif ($definition['price'] > 0) {
                    $lockedPlayer->decrement('coins', $definition['price']);
                }

                $pet = $lockedPlayer->pokoPets()->create([
                    'type' => $type,
                    'name' => mb_substr(trim($name) ?: $definition['name'], 0, 50),
                    'selected' => false,
                    'state' => 'idle',
                    'health' => 5,
                    'max_health' => 5,
                    'speed' => 1,
                    'agility' => 1,
                    'power' => 1,
                    'experience' => 0,
                    'level' => 1,
                    'abilities' => [501],
                ]);

                return ['statusCode' => 0, 'message' => 'Pokopet added.', 'pet' => $this->toValueObject($pet)];
            });
        } catch (Throwable) {
            return ['statusCode' => 2, 'message' => 'Could not save the Pokopet.', 'pet' => null];
        }
    }

    public function updateState(User $player, int $petId, string $state): ?stdClass
    {
        if (! in_array($state, self::ALLOWED_STATES, true)) {
            return null;
        }

        $pet = $player->pokoPets()->find($petId);
        if ($pet === null) {
            return null;
        }
        $pet->update(['state' => $state]);

        return $this->toValueObject($pet->refresh());
    }

    public function select(User $player, int $petId): bool
    {
        return DB::transaction(function () use ($player, $petId): bool {
            $player->pokoPets()->where('state', 'walking')->update(['state' => 'idle']);
            $player->pokoPets()->update(['selected' => false]);
            if ($petId < 0) {
                return true;
            }

            return $player->pokoPets()->whereKey($petId)->update(['selected' => true, 'state' => 'walking']) > 0;
        });
    }

    public function remove(User $player, int $petId): bool
    {
        return $player->pokoPets()->whereKey($petId)->delete() > 0;
    }

    public function feed(User $player, int $petId): ?int
    {
        $pet = $player->pokoPets()->find($petId);
        if ($pet === null) {
            return null;
        }
        $pet->update(['health' => $pet->max_health, 'last_fed' => now(), 'state' => 'eating']);

        return (int) $pet->max_health;
    }

    public function increaseSelectedHealth(User $player): ?stdClass
    {
        $pet = $player->pokoPets()->where('selected', true)->first();
        if ($pet === null || $pet->health >= $pet->max_health) {
            return null;
        }
        $pet->increment('health');

        return $this->toValueObject($pet->refresh());
    }

    public function toValueObject(PokoPet $pet): stdClass
    {
        $value = (object) [
            'id' => (int) $pet->getKey(),
            'name' => (string) $pet->name,
            'type' => (string) $pet->type,
            'selected' => (bool) $pet->selected,
            'x' => (int) $pet->x,
            'y' => (int) $pet->y,
            'state' => (string) $pet->state,
            'abilities' => array_values(array_map('intval', $pet->abilities ?? [])),
            'properties' => (object) [
                'health' => (int) $pet->health,
                'maxHealth' => (int) $pet->max_health,
                'speed' => (int) $pet->speed,
                'agility' => (int) $pet->agility,
                'power' => (int) $pet->power,
                'experience' => (int) $pet->experience,
                'level' => (int) $pet->level,
            ],
        ];

        if ($pet->last_fed !== null) {
            $value->lastFed = $this->valueObjects->make('Date', ['date' => $pet->last_fed->getTimestamp() * 1000]);
        }

        return $value;
    }
}
