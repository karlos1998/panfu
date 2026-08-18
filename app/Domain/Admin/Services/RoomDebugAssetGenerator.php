<?php

namespace App\Domain\Admin\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class RoomDebugAssetGenerator
{
    public const STAGE_WIDTH = 772;

    public const STAGE_HEIGHT = 480;

    public function __construct(private readonly Filesystem $files) {}

    /** @return array{generated: int, missing: int, rooms: array<string, mixed>} */
    public function generate(string $ffdecJar, string $outputDirectory): array
    {
        if (! $this->files->isFile($ffdecJar)) {
            throw new RuntimeException("Nie znaleziono FFDec JAR: {$ffdecJar}");
        }

        $temporaryDirectory = sys_get_temp_dir().'/panfu-room-debug-'.bin2hex(random_bytes(6));
        $this->files->ensureDirectoryExists($temporaryDirectory);
        $this->files->ensureDirectoryExists($outputDirectory);

        $manifest = ['generated' => 0, 'missing' => 0, 'rooms' => []];

        try {
            foreach ($this->roomAssets() as $roomId => $swfPath) {
                $room = $this->extractRoom($roomId, $swfPath, $ffdecJar, $temporaryDirectory, $outputDirectory);
                $manifest['rooms'][$roomId] = $room;
                $room['walkAreaFrames'] === [] ? $manifest['missing']++ : $manifest['generated']++;
            }

            $this->files->put(
                $outputDirectory.'/manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
            );
        } finally {
            $this->files->deleteDirectory($temporaryDirectory);
        }

        return $manifest;
    }

    /** @return array<string, string> */
    private function roomAssets(): array
    {
        $assets = [];

        foreach ($this->files->glob(public_path('vendor/openpanfu/rooms/*/assets/room.swf')) as $path) {
            $roomId = basename(dirname(dirname($path)));

            if ($roomId !== 'home') {
                $assets[$roomId] = $path;
            }
        }

        ksort($assets, SORT_NATURAL | SORT_FLAG_CASE);

        return $assets;
    }

    /** @return array{walkAreaCharacterId: int|null, walkAreaFrames: array<int, array<string, mixed>>} */
    private function extractRoom(
        string $roomId,
        string $swfPath,
        string $ffdecJar,
        string $temporaryDirectory,
        string $outputDirectory,
    ): array {
        $roomTemporaryDirectory = $temporaryDirectory.'/'.$roomId;
        $xmlPath = $roomTemporaryDirectory.'/room.xml';
        $exportDirectory = $roomTemporaryDirectory.'/export';
        $this->files->ensureDirectoryExists($roomTemporaryDirectory);

        $this->run(['java', '-jar', $ffdecJar, '-cli', '-swf2xml', $swfPath, $xmlPath]);

        $document = new DOMDocument;
        $document->load($xmlPath, LIBXML_NONET | LIBXML_COMPACT);
        $xpath = new DOMXPath($document);
        $walkArea = $xpath->query("//item[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'walkarea']")->item(0);

        if (! $walkArea instanceof DOMElement || ! $walkArea->hasAttribute('characterId')) {
            return ['walkAreaCharacterId' => null, 'walkAreaFrames' => []];
        }

        $characterId = (int) $walkArea->getAttribute('characterId');
        $this->run([
            'java', '-jar', $ffdecJar, '-cli', '-format', 'sprite:svg', '-selectid', (string) $characterId,
            '-export', 'sprite', $exportDirectory, $swfPath,
        ]);

        $roomOutputDirectory = $outputDirectory.'/'.$roomId;
        $this->files->deleteDirectory($roomOutputDirectory);
        $this->files->ensureDirectoryExists($roomOutputDirectory);

        $frames = [];
        $knownHashes = [];

        foreach ($this->files->allFiles($exportDirectory) as $file) {
            if (strtolower($file->getExtension()) !== 'svg') {
                continue;
            }

            $svg = $this->files->get($file->getPathname());
            $hash = md5($svg);

            if (isset($knownHashes[$hash])) {
                continue;
            }

            $frameNumber = count($frames) + 1;
            $fileName = "walkarea-{$frameNumber}.svg";
            $this->files->put($roomOutputDirectory.'/'.$fileName, $this->colorizeWalkArea($svg));
            $frames[] = [
                ...$this->frameMetadata($svg, "/vendor/panfu-admin/room-debug/{$roomId}/{$fileName}"),
                'transform' => $this->placementMatrix($walkArea),
            ];
            $knownHashes[$hash] = true;
        }

        return [
            'walkAreaCharacterId' => $characterId,
            'walkAreaFrames' => $frames,
        ];
    }

    /** @return array<string, int|float|string> */
    private function frameMetadata(string $svg, string $url): array
    {
        $document = new DOMDocument;
        $document->loadXML($svg, LIBXML_NONET | LIBXML_COMPACT);
        $root = $document->documentElement;
        $group = $root?->firstElementChild;
        $width = (float) preg_replace('/[^0-9.\-]/', '', $root?->getAttribute('width') ?: (string) self::STAGE_WIDTH);
        $height = (float) preg_replace('/[^0-9.\-]/', '', $root?->getAttribute('height') ?: (string) self::STAGE_HEIGHT);
        $x = 0.0;
        $y = 0.0;

        if ($group instanceof DOMElement && preg_match('/matrix\([^,]+,[^,]+,[^,]+,[^,]+,\s*([\d.\-]+),\s*([\d.\-]+)\)/', $group->getAttribute('transform'), $matches)) {
            $x = -((float) $matches[1]);
            $y = -((float) $matches[2]);
        }

        return compact('url', 'x', 'y', 'width', 'height');
    }

    /** @return array{a: float, b: float, c: float, d: float, tx: float, ty: float} */
    private function placementMatrix(DOMElement $placement): array
    {
        $matrix = $placement->getElementsByTagName('matrix')->item(0);

        if (! $matrix instanceof DOMElement) {
            return ['a' => 1.0, 'b' => 0.0, 'c' => 0.0, 'd' => 1.0, 'tx' => 0.0, 'ty' => 0.0];
        }

        return [
            'a' => $matrix->hasAttribute('scaleX') ? (float) $matrix->getAttribute('scaleX') : 1.0,
            'b' => $matrix->hasAttribute('rotateSkew1') ? (float) $matrix->getAttribute('rotateSkew1') : 0.0,
            'c' => $matrix->hasAttribute('rotateSkew0') ? (float) $matrix->getAttribute('rotateSkew0') : 0.0,
            'd' => $matrix->hasAttribute('scaleY') ? (float) $matrix->getAttribute('scaleY') : 1.0,
            'tx' => round(((float) $matrix->getAttribute('translateX')) / 20, 4),
            'ty' => round(((float) $matrix->getAttribute('translateY')) / 20, 4),
        ];
    }

    private function colorizeWalkArea(string $svg): string
    {
        $style = '<style>path,polygon,rect,circle,ellipse{fill:#22c55e!important;stroke:#14532d!important;stroke-width:1.5px!important} use{opacity:.82}</style>';

        return str_replace('</svg>', $style.'</svg>', $svg);
    }

    /** @param array<int, string> $command */
    private function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(120);
        $process->mustRun();
    }
}
