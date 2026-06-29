<?php

namespace Tests\Feature\Panfu;

use Tests\TestCase;

class OpenPanfuAssetAvailabilityTest extends TestCase
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
}
