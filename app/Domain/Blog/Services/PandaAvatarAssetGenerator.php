<?php

namespace App\Domain\Blog\Services;

use App\Models\Inventory;
use Illuminate\Filesystem\Filesystem;
use Imagick;
use RuntimeException;
use Symfony\Component\Process\Process;

class PandaAvatarAssetGenerator
{
    public function __construct(private readonly Filesystem $files) {}

    /** @return array{items: int, colors: int} */
    public function generate(string $ffdecJar, string $outputDirectory): array
    {
        if (! $this->files->isFile($ffdecJar)) {
            throw new RuntimeException("Nie znaleziono FFDec JAR: {$ffdecJar}");
        }

        $temporary = sys_get_temp_dir().'/panfu-blog-avatars-'.bin2hex(random_bytes(6));
        $this->files->ensureDirectoryExists($temporary);
        $this->files->ensureDirectoryExists($outputDirectory);

        try {
            $this->exportFrame($ffdecJar, public_path('vendor/openpanfu/assets/ui/common/AvatarMale.swf'), $temporary.'/male');
            $this->exportFrame($ffdecJar, public_path('vendor/openpanfu/assets/ui/common/AvatarFemale.swf'), $temporary.'/female');

            $colors = Inventory::query()->where('active', true)->whereHas('item', fn ($query) => $query->where('type', 1))->pluck('item_id')->unique()->values();
            $colors->whenEmpty(fn ($collection) => $collection->push(1001));

            foreach ($colors as $colorId) {
                foreach (['male', 'female'] as $sex) {
                    $this->tint($temporary."/{$sex}/1.png", $outputDirectory."/avatar-{$sex}-{$colorId}.png", $this->colors()[(int) $colorId] ?? '#3d3d3d');
                }
            }

            $itemIds = Inventory::query()
                ->where('active', true)
                ->whereHas('item', fn ($query) => $query->whereNotIn('type', [0, 1, 13, 14, 16, 17, 20, 50, 98]))
                ->pluck('item_id')->unique()->values();

            foreach ($itemIds as $itemId) {
                $swf = public_path("vendor/openpanfu/assets/library/clothesPlayercard/ClothesPlayercard_{$itemId}.swf");
                if (! $this->files->isFile($swf)) {
                    continue;
                }

                $target = $temporary."/item-{$itemId}";
                $this->exportFrame($ffdecJar, $swf, $target);
                if ($this->files->isFile($target.'/1.png')) {
                    $this->makeTransparent($target.'/1.png', $outputDirectory."/{$itemId}.png", '#ffffff');
                }
            }

            return ['items' => $itemIds->count(), 'colors' => $colors->count()];
        } finally {
            $this->files->deleteDirectory($temporary);
        }
    }

    private function exportFrame(string $jar, string $swf, string $output): void
    {
        $process = new Process(['java', '-jar', $jar, '-cli', '-format', 'frame:png', '-export', 'frame', $output, $swf]);
        $process->setTimeout(120);
        $process->mustRun();
    }

    private function tint(string $source, string $target, string $hex): void
    {
        $image = new Imagick($source);
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');

        $iterator = $image->getPixelIterator();
        foreach ($iterator as $row) {
            foreach ($row as $pixel) {
                $color = $pixel->getColor();
                if ($color['r'] > 70 && $color['r'] > $color['g'] * 1.35 && $color['r'] > $color['b'] * 1.35) {
                    $shade = $color['r'] / 255;
                    $pixel->setColor(sprintf('rgba(%d,%d,%d,%.4f)', $red * $shade, $green * $shade, $blue * $shade, $color['a']));
                }
            }
            $iterator->syncIterator();
        }

        $image->setImageFormat('png');
        $image->transparentPaintImage('#ffcc99', 0, 0, false);
        $image->writeImage($target);
        $image->clear();
    }

    private function makeTransparent(string $source, string $target, string $color): void
    {
        $image = new Imagick($source);
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
        $image->transparentPaintImage($color, 0, 0, false);
        $image->setImageFormat('png');
        $image->writeImage($target);
        $image->clear();
    }

    /** @return array<int, string> */
    private function colors(): array
    {
        $xml = simplexml_load_file(public_path('vendor/openpanfu/conf/config.xml'));
        $colors = [];
        foreach ($xml?->colors->color ?? [] as $color) {
            $colors[(int) $color['id']] = '#'.ltrim((string) $color['value'], '#');
        }

        return $colors;
    }
}
