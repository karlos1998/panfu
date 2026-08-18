<?php

namespace App\Domain\Panfu\Services;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Imagick;
use ImagickPixel;
use RuntimeException;

class PandaPlayercardRenderer
{
    private const BASE_X = 62;

    private const BASE_Y = 44;

    private const HEIGHT = 240;

    private const WIDTH = 230;

    public function __construct(private readonly Filesystem $files) {}

    public function render(?User $user): string
    {
        $layers = $this->layers($user);
        $directory = storage_path('app/panfu/playercards');
        $this->files->ensureDirectoryExists($directory);

        $target = $directory.'/'.$this->fingerprint($layers).'.png';
        if ($this->files->isFile($target)) {
            return $target;
        }

        $temporary = $target.'.'.bin2hex(random_bytes(6)).'.tmp';
        $canvas = new Imagick;

        try {
            $canvas->newImage(self::WIDTH, self::HEIGHT, new ImagickPixel('transparent'), 'png');

            foreach ($layers as $layer) {
                $image = new Imagick($layer['path']);
                try {
                    $canvas->compositeImage($image, Imagick::COMPOSITE_OVER, $layer['x'], $layer['y']);
                } finally {
                    $image->clear();
                }
            }

            $canvas->setImageFormat('png');
            $canvas->writeImage($temporary);
            $this->files->move($temporary, $target);
        } finally {
            $canvas->clear();
            if ($this->files->exists($temporary)) {
                $this->files->delete($temporary);
            }
        }

        return $target;
    }

    /** @return array<int, array{path: string, x: int, y: int}> */
    private function layers(?User $user): array
    {
        $entries = $this->activeInventory($user);
        $colorId = (int) ($entries->first(fn (Inventory $entry) => $entry->item?->type === 1)?->item_id ?? 1001);
        $sex = $user?->sex ? 'female' : 'male';
        $base = $this->basePath($sex, $colorId);
        $background = $entries->first(fn (Inventory $entry) => $entry->item?->type === 2);

        return collect([$background ? $this->itemLayer($background) : null])
            ->filter()
            ->push(['path' => $base, 'x' => self::BASE_X, 'y' => self::BASE_Y])
            ->concat($entries
                ->reject(fn (Inventory $entry) => in_array($entry->item?->type, [0, 1, 2, 13, 14, 16, 17, 20, 50, 98], true))
                ->sortBy(fn (Inventory $entry) => $entry->item?->z)
                ->map(fn (Inventory $entry) => $this->itemLayer($entry))
                ->filter())
            ->values()
            ->all();
    }

    /** @return Collection<int, Inventory> */
    private function activeInventory(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        $entries = $user->relationLoaded('inventoryEntries')
            ? $user->inventoryEntries
            : $user->inventoryEntries()->where('active', true)->with('item')->get();

        return $entries->where('active', true)->filter(fn (Inventory $entry) => $entry->item !== null);
    }

    /** @return array{path: string, x: int, y: int}|null */
    private function itemLayer(Inventory $entry): ?array
    {
        $path = public_path("vendor/panfu-blog/playercard/{$entry->item_id}.png");

        return $this->files->isFile($path) ? ['path' => $path, 'x' => 0, 'y' => 0] : null;
    }

    private function basePath(string $sex, int $colorId): string
    {
        $path = public_path("vendor/panfu-blog/playercard/avatar-{$sex}-{$colorId}.png");
        if ($this->files->isFile($path)) {
            return $path;
        }

        $fallback = public_path("vendor/panfu-blog/playercard/avatar-{$sex}-1001.png");
        if (! $this->files->isFile($fallback)) {
            throw new RuntimeException("Brak bazowego playercardu pandy: {$fallback}");
        }

        return $fallback;
    }

    /** @param array<int, array{path: string, x: int, y: int}> $layers */
    private function fingerprint(array $layers): string
    {
        return hash('xxh128', json_encode(array_map(fn (array $layer) => [
            $layer['path'],
            $layer['x'],
            $layer['y'],
            $this->files->lastModified($layer['path']),
            $this->files->size($layer['path']),
        ], $layers), JSON_THROW_ON_ERROR));
    }
}
