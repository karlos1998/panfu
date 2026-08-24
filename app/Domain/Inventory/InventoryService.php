<?php

namespace App\Domain\Inventory;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class InventoryService
{
    private const FURNITURE_TYPES = [0, 13, 14, 17, 50];

    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    /** @return list<TypedObject> */
    public function itemsFor(User $player, bool $active): array
    {
        return $player->inventoryEntries()
            ->with('item')
            ->where('active', $active)
            ->get()
            ->filter(fn (Inventory $entry): bool => $entry->item !== null)
            ->map(fn (Inventory $entry): TypedObject => $this->itemValueObject($entry->item, $entry))
            ->values()
            ->all();
    }

    /** @return list<TypedObject> */
    public function furnitureFor(User $player): array
    {
        return $player->inventoryEntries()
            ->with('item')
            ->get()
            ->filter(fn (Inventory $entry): bool => $entry->item !== null && $this->isFurniture($entry->item))
            ->map(fn (Inventory $entry): TypedObject => $this->furnitureValueObject($entry->item, $entry))
            ->values()
            ->all();
    }

    public function has(User $player, int $itemId): bool
    {
        return $player->inventoryEntries()->where('item_id', $itemId)->exists();
    }

    public function add(User $player, int $itemId, bool $active = false): ?Inventory
    {
        if (! Item::query()->whereKey($itemId)->exists()) {
            return null;
        }

        return $player->inventoryEntries()->firstOrCreate(
            ['item_id' => $itemId],
            ['active' => $active, 'bought' => true, 'x' => 0, 'y' => 0, 'rot' => 0, 'room' => 0],
        );
    }

    /** @return array{statusCode:int,message:string,valueObject:?TypedObject} */
    public function purchase(User $player, int $itemId, bool $questReward = false): array
    {
        return DB::transaction(function () use ($player, $itemId, $questReward): array {
            $lockedPlayer = User::query()->lockForUpdate()->find($player->getKey());
            $item = Item::query()->find($itemId);

            if ($lockedPlayer === null || $item === null) {
                return ['statusCode' => 1, 'message' => "Item doesn't exist.", 'valueObject' => null];
            }

            $existing = $lockedPlayer->inventoryEntries()->where('item_id', $itemId)->first();
            if ($existing !== null) {
                return [
                    'statusCode' => 0,
                    'message' => 'Item already',
                    'valueObject' => $this->valueObjectFor($item, $existing),
                ];
            }

            if (! $questReward && (bool) $item->premium && (int) $lockedPlayer->goldpanda <= 0) {
                return ['statusCode' => 5, 'message' => 'A Gold Panda membership is required.', 'valueObject' => null];
            }

            if ((int) $lockedPlayer->coins < (int) $item->price) {
                return ['statusCode' => 6, 'message' => 'Not enough coins.', 'valueObject' => null];
            }

            $lockedPlayer->decrement('coins', (int) $item->price);
            $entry = $lockedPlayer->inventoryEntries()->create([
                'item_id' => $item->getKey(),
                'active' => false,
                'bought' => true,
                'x' => 0,
                'y' => 0,
                'rot' => 0,
                'room' => 0,
            ]);

            return [
                'statusCode' => 0,
                'message' => 'Item added!',
                'valueObject' => $this->valueObjectFor($item, $entry),
            ];
        });
    }

    /** @param list<mixed> $activeItems @param list<mixed> $inactiveItems */
    public function updateEquipped(User $player, array $activeItems, array $inactiveItems): void
    {
        DB::transaction(function () use ($player, $activeItems, $inactiveItems): void {
            $this->setActiveState($player, $activeItems, true);
            $this->setActiveState($player, $inactiveItems, false);
        });
    }

    /** @param list<int> $itemIds */
    public function remove(User $player, array $itemIds): void
    {
        $player->inventoryEntries()->whereIn('item_id', array_map('intval', $itemIds))->delete();
    }

    /** @param list<mixed> $furniture */
    public function updateFurniture(User $player, array $furniture): void
    {
        DB::transaction(function () use ($player, $furniture): void {
            foreach ($furniture as $data) {
                $id = $this->property($data, 'id');
                if (! is_numeric($id)) {
                    continue;
                }

                $room = $this->property($data, 'room', $this->property($data, 'roomID', 0));
                $player->inventoryEntries()
                    ->where('item_id', (int) $id)
                    ->whereHas('item', fn ($query) => $query->whereIn('type', self::FURNITURE_TYPES))
                    ->update([
                        'x' => (int) $this->property($data, 'x', 0),
                        'y' => (int) $this->property($data, 'y', 0),
                        'rot' => (int) $this->property($data, 'rot', 0),
                        'room' => (int) $room,
                        'active' => (bool) $this->property($data, 'active', false),
                    ]);
            }
        });
    }

    public function itemValueObject(Item $item, ?Inventory $entry = null): TypedObject
    {
        return $this->valueObjects->make('Item', [
            'id' => (int) $item->getKey(),
            'name' => (string) $item->name,
            'type' => str_pad((string) $item->type, 2, '0', STR_PAD_LEFT),
            'price' => (int) $item->price,
            'zettSort' => (int) $item->z,
            'premium' => (bool) $item->premium,
            'bought' => true,
            'active' => (bool) ($entry?->active ?? false),
        ]);
    }

    public function furnitureValueObject(Item $item, ?Inventory $entry = null): TypedObject
    {
        $room = (int) ($entry?->room ?? 0);

        return $this->valueObjects->make('FurnitureData', [
            'uid' => (int) ($entry?->getKey() ?? $item->getKey()),
            'id' => (int) $item->getKey(),
            'type' => str_pad((string) $item->type, 2, '0', STR_PAD_LEFT),
            'active' => (bool) ($entry?->active ?? false),
            'premium' => true,
            'bought' => (bool) ($entry?->bought ?? true),
            'x' => (int) ($entry?->x ?? 0),
            'y' => (int) ($entry?->y ?? 0),
            'rot' => (int) ($entry?->rot ?? 0),
            'room' => $room,
            'roomID' => $room,
        ]);
    }

    private function valueObjectFor(Item $item, ?Inventory $entry = null): TypedObject
    {
        return $this->isFurniture($item)
            ? $this->furnitureValueObject($item, $entry)
            : $this->itemValueObject($item, $entry);
    }

    private function isFurniture(Item $item): bool
    {
        return in_array((int) $item->type, self::FURNITURE_TYPES, true);
    }

    /** @param list<mixed> $items */
    private function setActiveState(User $player, array $items, bool $active): void
    {
        $ids = array_values(array_filter(array_map(
            fn (mixed $item): int => (int) $this->property($item, 'id', 0),
            $items,
        )));

        if ($ids !== []) {
            $player->inventoryEntries()->whereIn('item_id', $ids)->update(['active' => $active]);
        }
    }

    private function property(mixed $data, string $name, mixed $default = null): mixed
    {
        if ($data instanceof TypedObject) {
            return $data->get($name, $default);
        }
        if (is_object($data)) {
            return $data->{$name} ?? $default;
        }
        if (is_array($data)) {
            return $data[$name] ?? $default;
        }

        return $default;
    }
}
