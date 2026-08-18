<?php

namespace Tests\Feature\Panfu;

use Tests\TestCase;

class PanfuAssetAvailabilityTest extends TestCase
{
    public function test_catalogue_xmls_and_pages_are_available(): void
    {
        $catalogues = [
            'ClothesCatalogue.xml' => 'clothes',
            'ECardCatalogue.xml' => 'ecard',
            'FurnitureCatalogue.xml' => 'furniture',
            'BollyFurnitureCatalogue.xml' => 'bollyFurniture',
            'HouseupgradeCatalogue.xml' => 'houseupgrade',
            'PokopetsCatalogue.xml' => 'pokopets',
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

        $bollyCataloguePath = public_path('vendor/openpanfu/conf/catalogues/BollyCatalogue.xml');

        $this->assertFileExists($bollyCataloguePath);
        $this->assertNotFalse(simplexml_load_file($bollyCataloguePath));
    }

    public function test_pop_it_level_configs_and_images_are_available(): void
    {
        $configPath = public_path('vendor/openpanfu/swf/games/balloons/conf/config.xml');

        $this->assertFileExists($configPath);

        $config = simplexml_load_file($configPath);
        $this->assertNotFalse($config);

        $levelCount = (int) $config['levelCount'];
        $this->assertSame(32, $levelCount);

        foreach (range(1, $levelCount) as $level) {
            $levelPath = public_path("vendor/openpanfu/swf/games/balloons/conf/level{$level}.xml");

            $this->assertFileExists($levelPath);

            $levelConfig = simplexml_load_file($levelPath);
            $this->assertNotFalse($levelConfig);

            $this->assertFileExists(
                public_path('vendor/openpanfu/swf/games/balloons/img/'.(string) $levelConfig['img']),
            );
        }
    }

    public function test_available_pokopet_race_snippets_are_valid(): void
    {
        foreach (['DE', 'EN'] as $language) {
            $path = public_path("vendor/openpanfu/assets/petrace/conf/pokopets_race_snippets_{$language}.xml");

            $this->assertFileExists($path);
            $this->assertNotFalse(simplexml_load_file($path));
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
        foreach ($this->minigameSnippetDirectories() as $game) {
            foreach (['DE', 'EN', 'PL'] as $language) {
                $this->assertFileExists(
                    public_path("vendor/openpanfu/swf/games/{$game}/conf/snippets_{$language}.xml"),
                    "Missing {$game} snippets for {$language}",
                );
            }
        }
    }

    public function test_global_snippet_archives_are_available_for_supported_languages(): void
    {
        foreach (['DE', 'EN', 'PL'] as $language) {
            $path = public_path("vendor/openpanfu/conf/allSnippets/{$language}.zip");

            $this->assertFileExists($path, "Missing global snippet archive for {$language}");
            $this->assertGreaterThan(1024, filesize($path), "{$language} snippet archive is unexpectedly small");
        }
    }

    public function test_language_placeholder_snippet_references_are_available(): void
    {
        foreach ($this->configurationFilesWithSnippetPlaceholders() as $configurationFile) {
            $configurationPath = public_path("vendor/openpanfu/{$configurationFile}");
            $this->assertFileExists($configurationPath);

            $xml = simplexml_load_file($configurationPath);
            $this->assertNotFalse($xml);

            foreach ($xml->xpath('//snippets') as $snippets) {
                $snippetPath = (string) $snippets['path'];

                if (! str_contains($snippetPath, '$$lang$$')) {
                    continue;
                }

                foreach (['DE', 'EN', 'PL'] as $language) {
                    $localizedPath = str_replace('$$lang$$', $language, $snippetPath);

                    $this->assertFileExists(
                        public_path("vendor/openpanfu/{$localizedPath}"),
                        "Missing localized snippet {$localizedPath} referenced by {$configurationFile}",
                    );
                }
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
            'ChristmasWorkshop/assets/correct.mp3',
            'ChristmasWorkshop/assets/correct2.mp3',
            'ChristmasWorkshop/assets/correct3.mp3',
            'ChristmasWorkshop/assets/correct4.mp3',
            'ChristmasWorkshop/assets/helper.mp3',
            'ChristmasWorkshop/assets/switch.mp3',
            'ChristmasWorkshop/assets/wrong.mp3',
            'PanfuDefence/assets/bollyShot.mp3',
            'PanfuDefence/assets/button.mp3',
            'PanfuDefence/assets/coin1.mp3',
            'PanfuDefence/assets/coin2.mp3',
            'PanfuDefence/assets/coin3.mp3',
            'PanfuDefence/assets/coin4.mp3',
            'PanfuDefence/assets/gameWin.mp3',
            'PanfuDefence/assets/hint.mp3',
            'PanfuDefence/assets/kill1.mp3',
            'PanfuDefence/assets/kill2.mp3',
            'PanfuDefence/assets/kill3.mp3',
            'PanfuDefence/assets/kill4.mp3',
            'PanfuDefence/assets/levelFail.mp3',
            'PanfuDefence/assets/levelWin.mp3',
            'PanfuDefence/assets/lifeLost.mp3',
            'PanfuDefence/assets/nextWave.mp3',
            'PanfuDefence/assets/pokopetShot.mp3',
            'PanfuDefence/assets/upgrade.mp3',
            'PanfuDefence/assets/woobyShot.mp3',
            'PixelGuards/assets/playerDetected.mp3',
            'PixelGuards/assets/reachedGoal.mp3',
            'PixelGuards/assets/startButton.mp3',
            'PixelGuards/assets/usedSwitch.mp3',
            'Unlocker/assets/gameover.mp3',
            'Unlocker/assets/lampe.mp3',
            'Unlocker/assets/levelup.mp3',
            'Unlocker/assets/lockcorrect.mp3',
            'Unlocker/assets/lockwrong.mp3',
            'Unlocker/assets/tile.mp3',
            'labyrinth/assets/success.mp3',
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

    private function minigameSnippetDirectories(): array
    {
        return [
            'BeSmarter',
            'ChristmasWorkshop',
            'EnglishMaster',
            'KungFu',
            'MazeGame',
            'PanfuDefence',
            'PixelGuards',
            'ShoppingList',
            'Unlocker',
            'baloon',
            'balloons',
            'bell',
            'cast_away',
            'filler',
            'football',
            'fourwins',
            'heli',
            'jumper',
            'labyrinth',
            'mahjong',
            'numbers',
            'parkowanie',
            'pingpong',
            'quiz',
            'rps',
            'sintepan',
            'skatepark',
            'smartrace',
            'surfing',
            'walizka',
            'worldmap',
            'zuma',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function configurationFilesWithSnippetPlaceholders(): array
    {
        return [
            'conf/config.xml',
            'rooms/newcave/conf/newcave.xml',
            'rooms/racetrack/conf/racetrack.xml',
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
