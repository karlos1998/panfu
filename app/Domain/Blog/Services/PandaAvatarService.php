<?php

namespace App\Domain\Blog\Services;

use App\Models\User;

class PandaAvatarService
{
    /** @return array{sex: string, base: string, background: string|null, layers: array<int, string>} */
    public function forUser(?User $user): array
    {
        if (! $user) {
            return ['sex' => 'male', 'base' => '/vendor/panfu-blog/playercard/avatar-male-1001.png', 'background' => null, 'layers' => []];
        }

        $entries = $user->relationLoaded('inventoryEntries')
            ? $user->inventoryEntries
            : $user->inventoryEntries()->where('active', true)->with('item')->get();

        $active = $entries->where('active', true)->filter(fn ($entry) => $entry->item !== null);
        $colorItem = $active->first(fn ($entry) => $entry->item->type === 1);
        $background = $active->first(fn ($entry) => $entry->item->type === 2);

        return [
            'sex' => $user->sex ? 'female' : 'male',
            'base' => $this->baseUrl($user->sex ? 'female' : 'male', (int) ($colorItem?->item_id ?? 1001)),
            'background' => $this->assetUrl($background?->item_id),
            'layers' => $active
                ->reject(fn ($entry) => in_array($entry->item->type, [0, 1, 2, 13, 14, 16, 17, 20, 50, 98], true))
                ->sortBy(fn ($entry) => $entry->item->z)
                ->map(fn ($entry) => $this->assetUrl($entry->item_id))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function assetUrl(?int $itemId): ?string
    {
        if (! $itemId) {
            return null;
        }

        $png = public_path("vendor/panfu-blog/playercard/{$itemId}.png");

        return is_file($png) ? "/vendor/panfu-blog/playercard/{$itemId}.png" : null;
    }

    private function baseUrl(string $sex, int $colorId): string
    {
        $path = "/vendor/panfu-blog/playercard/avatar-{$sex}-{$colorId}.png";

        return is_file(public_path($path)) ? $path : "/vendor/panfu-blog/playercard/avatar-{$sex}-1001.png";
    }
}
