<?php

namespace Tests\Feature\Panfu;

use Tests\TestCase;

class PanfuAssetAvailabilityTest extends TestCase
{
    public function test_catalogue_xmls_and_pages_are_available(): void
    {
        $catalogues = [
            'ClothesCatalogue.xml' => 'clothes',
            'FurnitureCatalogue.xml' => 'furniture',
            'BollyFurnitureCatalogue.xml' => 'bollyFurniture',
            'HouseupgradeCatalogue.xml' => 'houseupgrade',
        ];

        foreach ($catalogues as $catalogue => $assetFolder) {
            $cataloguePath = public_path("vendor/openpanfu/conf/catalogues/{$catalogue}");

            $this->assertFileExists($cataloguePath);

            $xml = simplexml_load_file($cataloguePath);
            $this->assertNotFalse($xml);

            foreach ($xml->xpath('//img') as $image) {
                $filename = (string) $image['filename'];

                $this->assertFileExists(
                    public_path("vendor/openpanfu/assets/catalogue/{$assetFolder}/{$filename}"),
                    "Missing catalogue page {$assetFolder}/{$filename}",
                );
            }
        }
    }

    public function test_flying_component_audio_files_are_available(): void
    {
        $configPath = public_path('vendor/openpanfu/features/flyingComponent/conf/flyingComponent.xml');

        $this->assertFileExists($configPath);

        $xml = simplexml_load_file($configPath);
        $this->assertNotFalse($xml);

        foreach ($xml->xpath('//module/elements/element') as $element) {
            $animation = (string) $element['id'];

            $this->assertFileExists(
                public_path("vendor/openpanfu/features/flyingComponent/audio/{$animation}fly.mp3"),
                "Missing flying sound for {$animation}",
            );
            $this->assertFileExists(
                public_path("vendor/openpanfu/features/flyingComponent/audio/{$animation}destroy.mp3"),
                "Missing destroy sound for {$animation}",
            );
        }
    }

    public function test_reported_furniture_inventory_assets_are_available(): void
    {
        foreach ([103199, 104421] as $itemId) {
            $this->assertFileExists(
                public_path("vendor/openpanfu/rooms/home/assets/furniture_icons/FurnitureInventory_{$itemId}.swf"),
            );
        }

        $this->assertFileExists(
            public_path('vendor/openpanfu/rooms/home/assets/furniture/FurnitureItem3D_104421.swf'),
        );
    }

    public function test_minigame_movies_are_available(): void
    {
        foreach ([...range(1, 7), ...range(9, 56)] as $gameId) {
            $path = public_path("vendor/openpanfu/swf/games/game{$gameId}.swf");

            $this->assertFileExists($path, "Missing minigame movie game{$gameId}.swf");
            $this->assertGreaterThan(1024, filesize($path), "Minigame movie game{$gameId}.swf is unexpectedly small");
        }
    }

    public function test_minigame_snippet_configs_are_available(): void
    {
        foreach (['BeSmarter', 'EnglishMaster', 'MazeGame', 'bell', 'fourwins', 'rps'] as $game) {
            foreach (['DE', 'EN', 'PL'] as $language) {
                $this->assertFileExists(
                    public_path("vendor/openpanfu/swf/games/{$game}/conf/snippets_{$language}.xml"),
                    "Missing {$game} snippets for {$language}",
                );
            }
        }
    }

    public function test_minigame_support_audio_files_are_available(): void
    {
        $assets = [
            'BeSmarter/assets/applause.mp3',
            'BeSmarter/assets/correct.mp3',
            'BeSmarter/assets/intro.mp3',
            'BeSmarter/assets/outro.mp3',
            'BeSmarter/assets/questionBG.mp3',
            'BeSmarter/assets/timeout.mp3',
            'BeSmarter/assets/wrong.mp3',
            'MazeGame/assets/bg.mp3',
            'MazeGame/assets/blocker.mp3',
            'MazeGame/assets/cd.mp3',
            'MazeGame/assets/coin.mp3',
            'MazeGame/assets/exit.mp3',
            'MazeGame/assets/expand.mp3',
            'MazeGame/assets/explode.mp3',
            'MazeGame/assets/hammer.mp3',
            'MazeGame/assets/invalid.mp3',
            'MazeGame/assets/lost.mp3',
            'MazeGame/assets/move.mp3',
            'MazeGame/assets/place.mp3',
            'MazeGame/assets/spawn.mp3',
            'MazeGame/assets/turn.mp3',
            'MazeGame/assets/walk.mp3',
        ];

        foreach ($assets as $asset) {
            $this->assertFileExists(
                public_path("vendor/openpanfu/swf/games/{$asset}"),
                "Missing minigame support asset {$asset}",
            );
        }
    }

    public function test_english_master_level_assets_are_available_when_the_cdn_provides_them(): void
    {
        $levelConfigs = glob(public_path('vendor/openpanfu/swf/games/EnglishMaster/level*cat*e.xml')) ?: [];
        sort($levelConfigs);

        $this->assertCount(12, $levelConfigs);

        foreach ($levelConfigs as $configPath) {
            $xml = simplexml_load_file($configPath);
            $this->assertNotFalse($xml);

            $paths = [
                ((string) $xml->path['sndPath']).'applause.mp3',
                ((string) $xml->path['sndPath']).'wrong.mp3',
            ];

            foreach ($xml->xpath('//emimage') as $image) {
                $paths[] = ((string) $xml->path['imgPath']).trim((string) $image);
            }

            foreach ($xml->xpath('//emsound') as $sound) {
                $paths[] = ((string) $xml->path['voicePath']).trim((string) $sound);
            }

            foreach (array_unique($paths) as $asset) {
                if (in_array($asset, $this->knownProductionMinigameGaps(), true)) {
                    continue;
                }

                $this->assertFileExists(
                    public_path("vendor/openpanfu/{$asset}"),
                    "Missing EnglishMaster asset {$asset}",
                );
            }
        }
    }

    public function test_downloadable_minigame_swf_references_are_available(): void
    {
        foreach (glob(public_path('vendor/openpanfu/swf/games/*.swf')) ?: [] as $moviePath) {
            preg_match_all(
                '#swf/games/[A-Za-z0-9_./-]+\.(?:xml|mp3|jpg|png|swf)#',
                $this->readSwfBody($moviePath),
                $matches,
            );

            foreach (array_unique($matches[0]) as $asset) {
                if (in_array($asset, $this->knownProductionMinigameGaps(), true)) {
                    continue;
                }

                $this->assertFileExists(
                    public_path("vendor/openpanfu/{$asset}"),
                    "Missing minigame SWF dependency {$asset} referenced by ".basename($moviePath),
                );
            }
        }
    }

    /**
     * These references are present in production configs or SWFs, but the same production CDN returns 404.
     */
    private function knownProductionMinigameGaps(): array
    {
        return [
            'swf/games/EnglishMaster/assets/01level/01cat/images/21img.jpg',
            'swf/games/EnglishMaster/assets/01level/01cat/images/22img.jpg',
            'swf/games/EnglishMaster/assets/01level/01cat/images/23img.jpg',
            'swf/games/EnglishMaster/assets/01level/01cat/images/24img.jpg',
            'swf/games/EnglishMaster/assets/01level/01cat/voice/21voice.mp3',
            'swf/games/EnglishMaster/assets/01level/01cat/voice/22voice.mp3',
            'swf/games/EnglishMaster/assets/01level/01cat/voice/23voice.mp3',
            'swf/games/EnglishMaster/assets/01level/01cat/voice/24voice.mp3',
            'swf/games/EnglishMaster/assets/03level/01cat/images/20img.jpg',
            'swf/games/EnglishMaster/level01cat01h.xml',
            'swf/games/EnglishMaster/level01cat02h.xml',
            'swf/games/EnglishMaster/level01cat03h.xml',
            'swf/games/EnglishMaster/level01cat04h.xml',
            'swf/games/EnglishMaster/level02cat01h.xml',
            'swf/games/EnglishMaster/level02cat02h.xml',
            'swf/games/EnglishMaster/level02cat03h.xml',
            'swf/games/EnglishMaster/level02cat04h.xml',
            'swf/games/EnglishMaster/level03cat01h.xml',
            'swf/games/EnglishMaster/level03cat02h.xml',
            'swf/games/EnglishMaster/level03cat03h.xml',
            'swf/games/EnglishMaster/level03cat04h.xml',
        ];
    }

    private function readSwfBody(string $moviePath): string
    {
        $movie = file_get_contents($moviePath);
        $this->assertIsString($movie);

        $signature = substr($movie, 0, 3);

        if ($signature === 'FWS') {
            return substr($movie, 8);
        }

        $this->assertSame('CWS', $signature, "Unexpected SWF signature in {$moviePath}");

        $body = gzuncompress(substr($movie, 8));
        $this->assertIsString($body, "Could not decompress {$moviePath}");

        return $body;
    }
}
