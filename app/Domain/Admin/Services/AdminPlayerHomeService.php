<?php

namespace App\Domain\Admin\Services;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminPlayerHomeService
{
    /** @var array<int, int> */
    private const FURNITURE_TYPES = [13, 14, 17, 50];

    /** @param array{search: string, status: string, sort: string} $filters */
    public function paginatedHomes(array $filters): array
    {
        $query = User::query()
            ->with(['inventoryEntries' => fn ($query) => $query->whereHas('item', fn ($items) => $items->where('type', 0))->with('item')])
            ->withCount([
                'inventoryEntries as furniture_count' => fn ($query) => $this->furnitureQuery($query),
                'inventoryEntries as placed_furniture_count' => fn ($query) => $this->furnitureQuery($query)->where('active', true),
            ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort']);

        /** @var LengthAwarePaginator<int, User> $homes */
        $homes = $query->paginate(20)->withQueryString();

        return [
            'homes' => $homes->through(fn (User $user) => $this->summarize($user)),
            'filters' => $filters,
        ];
    }

    public function homeDetails(User $user): array
    {
        $backgrounds = $user->inventoryEntries()
            ->whereHas('item', fn ($query) => $query->where('type', 0))
            ->with('item')
            ->orderByDesc('active')
            ->orderBy('item_id')
            ->get();

        $furniture = $user->inventoryEntries()
            ->whereHas('item', fn ($query) => $query->whereIn('type', self::FURNITURE_TYPES))
            ->with('item')
            ->orderBy('room')
            ->orderBy('item_id')
            ->get();

        $backgroundAssets = $this->backgroundAssets();
        $activeBackground = $backgrounds->firstWhere('active', true);
        $activeBackgroundId = $activeBackground?->item_id ?? 100;

        return [
            'home' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'activeBackground' => $this->background($activeBackgroundId, $activeBackground?->item?->name, $backgroundAssets),
                'backgrounds' => $backgrounds->map(fn (Inventory $entry) => [
                    ...$this->background($entry->item_id, $entry->item?->name, $backgroundAssets),
                    'active' => $entry->active,
                ])->values(),
                'furniture' => $furniture->map(fn (Inventory $entry) => $this->furniture($entry))->values(),
                'roomNumbers' => $furniture->pluck('room')->unique()->sort()->values(),
                'furnitureCount' => $furniture->count(),
                'placedFurnitureCount' => $furniture->where('active', true)->count(),
            ],
            'client' => [
                'ruffleScript' => '/vendor/ruffle/ruffle.js',
                'stageWidth' => RoomDebugAssetGenerator::STAGE_WIDTH,
                'stageHeight' => RoomDebugAssetGenerator::STAGE_HEIGHT,
            ],
        ];
    }

    /** @param Builder<User> $query */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->when(is_numeric($search), fn (Builder $query) => $query->orWhereKey((int) $search));
                });
            })
            ->when($filters['status'] === 'furnished', fn (Builder $query) => $query->whereHas('inventoryEntries', fn ($entries) => $this->furnitureQuery($entries)))
            ->when($filters['status'] === 'placed', fn (Builder $query) => $query->whereHas('inventoryEntries', fn ($entries) => $this->furnitureQuery($entries)->where('active', true)))
            ->when($filters['status'] === 'empty', fn (Builder $query) => $query->whereDoesntHave('inventoryEntries', fn ($entries) => $this->furnitureQuery($entries)));
    }

    /** @param Builder<User> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name' => $query->orderBy('name'),
            'furniture' => $query->orderByDesc('furniture_count'),
            default => $query->latest(),
        };
    }

    private function furnitureQuery(Builder $query): Builder
    {
        return $query->whereHas('item', fn ($items) => $items->whereIn('type', self::FURNITURE_TYPES));
    }

    /** @return array<string, mixed> */
    private function summarize(User $user): array
    {
        $background = $user->inventoryEntries->firstWhere('active', true)
            ?? $user->inventoryEntries->first();

        return [
            'userId' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'backgroundId' => $background?->item_id ?? 100,
            'backgroundName' => $background?->item?->name ?? 'Domyślny domek na drzewie',
            'furnitureCount' => (int) $user->furniture_count,
            'placedFurnitureCount' => (int) $user->placed_furniture_count,
        ];
    }

    /** @return array<string, mixed> */
    private function furniture(Inventory $entry): array
    {
        $iconPath = public_path("vendor/openpanfu/rooms/home/assets/furniture_icons/FurnitureInventory_{$entry->item_id}.swf");
        $modelPath = public_path("vendor/openpanfu/rooms/home/assets/furniture/FurnitureItem3D_{$entry->item_id}.swf");

        return [
            'inventoryId' => $entry->id,
            'itemId' => $entry->item_id,
            'name' => $entry->item?->name ?? "Mebel #{$entry->item_id}",
            'type' => $entry->item?->type,
            'premium' => $entry->item?->premium ?? false,
            'placed' => $entry->active,
            'x' => $entry->x,
            'y' => $entry->y,
            'rotation' => $entry->rot,
            'room' => $entry->room,
            'iconUrl' => is_file($iconPath) ? "/vendor/openpanfu/rooms/home/assets/furniture_icons/FurnitureInventory_{$entry->item_id}.swf" : null,
            'modelUrl' => is_file($modelPath) ? "/vendor/openpanfu/rooms/home/assets/furniture/FurnitureItem3D_{$entry->item_id}.swf" : null,
        ];
    }

    /** @param array<int, string> $assets */
    private function background(int $itemId, ?string $name, array $assets): array
    {
        return [
            'itemId' => $itemId,
            'name' => $name ?: ($itemId === 100 ? 'Domyślny domek na drzewie' : "Domek #{$itemId}"),
            'swfUrl' => isset($assets[$itemId]) ? '/vendor/openpanfu/rooms/home/'.$assets[$itemId] : null,
        ];
    }

    /** @return array<int, string> */
    private function backgroundAssets(): array
    {
        $config = simplexml_load_file(public_path('vendor/openpanfu/rooms/home/conf/home.xml'));
        $assets = [];

        foreach ($config?->assets->asset ?? [] as $asset) {
            $id = (string) $asset['id'];

            if (ctype_digit($id) && str_starts_with((string) $asset['path'], 'assets/backgrounds/')) {
                $assets[(int) $id] = (string) $asset['path'];
            }
        }

        return $assets;
    }
}
