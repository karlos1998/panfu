<?php

namespace App\Domain\Pets;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\Bolly;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

final class BollyService
{
    private const STATES = ['normal', 'idle', 'sleeping', 'playing', 'eating', 'walking', 'denying', 'decrease', 'rescue', 'tricking'];

    /** @var array<int, array{name:string,x:int,y:int,z:int,colour:int,style:string,activity:string}>|null */
    private ?array $definitions = null;

    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    /** @return list<TypedObject> */
    public function forPlayer(User $player): array
    {
        return $player->bollies()->orderBy('id')->get()
            ->map(fn (Bolly $bolly): ?TypedObject => $this->toValueObject($bolly))
            ->filter()
            ->values()
            ->all();
    }

    /** @return array{statusCode:int,message:string,bolly:?TypedObject} */
    public function purchase(User $player, int $definitionId): array
    {
        $definition = $this->definitions()[$definitionId] ?? null;
        $item = Item::query()->find($definitionId);
        if ($definition === null || $item === null) {
            return ['statusCode' => 3, 'message' => 'Unknown Bolly.', 'bolly' => null];
        }

        return DB::transaction(function () use ($player, $definitionId, $definition, $item): array {
            $lockedPlayer = User::query()->lockForUpdate()->find($player->getKey());
            if ($lockedPlayer === null) {
                return ['statusCode' => 1, 'message' => 'Player not found.', 'bolly' => null];
            }
            if ($lockedPlayer->bollies()->where('definition_id', $definitionId)->lockForUpdate()->exists()) {
                return ['statusCode' => 10, 'message' => 'Bolly already owned.', 'bolly' => null];
            }
            if ((bool) $item->premium && (int) $lockedPlayer->goldpanda <= 0) {
                return ['statusCode' => 5, 'message' => 'A Gold Panda membership is required.', 'bolly' => null];
            }
            if ((int) $lockedPlayer->coins < (int) $item->price) {
                return ['statusCode' => 412, 'message' => 'Not enough coins.', 'bolly' => null];
            }
            if ((int) $item->price > 0) {
                $lockedPlayer->decrement('coins', (int) $item->price);
            }
            $bolly = $lockedPlayer->bollies()->create([
                'definition_id' => $definitionId,
                'activity' => $definition['activity'],
            ]);

            return [
                'statusCode' => 0,
                'message' => 'Bolly added.',
                'bolly' => $this->toValueObject($bolly, $item),
            ];
        });
    }

    public function update(User $player, TypedObject $value): ?TypedObject
    {
        $bolly = $player->bollies()->where('definition_id', (int) $value->get('id', 0))->first();
        if ($bolly === null) {
            return null;
        }
        $state = (string) $value->get('state', 'normal');
        $activity = trim((string) $value->get('activity', 'bollyNormal'));
        if (! in_array($state, self::STATES, true) || ! preg_match('/^[A-Za-z0-9_]{1,60}$/', $activity)) {
            return null;
        }
        $bolly->forceFill([
            'state' => $state,
            'activity' => $activity,
            'health' => $this->percentage($value->get('health', $bolly->health)),
            'rest' => $this->percentage($value->get('rest', $bolly->rest)),
            'energy' => $this->percentage($value->get('energy', $bolly->energy)),
        ])->save();

        return $this->toValueObject($bolly->refresh());
    }

    public function remove(User $player, int $definitionId): bool
    {
        return $player->bollies()->where('definition_id', $definitionId)->delete() > 0;
    }

    public function toValueObject(Bolly $bolly, ?Item $item = null): ?TypedObject
    {
        $definition = $this->definitions()[(int) $bolly->definition_id] ?? null;
        $item ??= Item::query()->find($bolly->definition_id);
        if ($definition === null || $item === null) {
            return null;
        }

        return $this->valueObjects->make('Bolly', [
            'id' => (int) $bolly->definition_id,
            'name' => (string) $item->name,
            'type' => $definition['name'],
            'price' => (int) $item->price,
            'state' => (string) $bolly->state,
            'activity' => (string) $bolly->activity,
            'health' => (int) $bolly->health,
            'rest' => (int) $bolly->rest,
            'energy' => (int) $bolly->energy,
            'x' => $definition['x'],
            'y' => $definition['y'],
            'z' => $definition['z'],
            'colour' => $definition['colour'],
            'style' => $definition['style'],
        ]);
    }

    /** @return array<int, array{name:string,x:int,y:int,z:int,colour:int,style:string,activity:string}> */
    private function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }
        $xml = simplexml_load_file(public_path('vendor/openpanfu/conf/config.xml'));
        $definitions = [];
        foreach ($xml?->bollies->characters->children() ?? [] as $name => $node) {
            /** @var SimpleXMLElement $node */
            $id = (int) $node['id'];
            $colour = (string) $node['colour'];
            $definitions[$id] = [
                'name' => (string) $name,
                'x' => (int) $node['x'],
                'y' => (int) $node['y'],
                'z' => (int) $node['z'],
                'colour' => str_starts_with($colour, '0x') ? (int) hexdec(substr($colour, 2)) : (int) $colour,
                'style' => (string) $node['style'],
                'activity' => (string) $node['normal'],
            ];
        }

        return $this->definitions = $definitions;
    }

    private function percentage(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }
}
