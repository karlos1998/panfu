<?php

namespace App\Console\Commands;

use App\Domain\Panfu\Services\PandaPlayercardAssetGenerator;
use Illuminate\Console\Command;

class GeneratePlayercardAssets extends Command
{
    protected $signature = 'panfu:generate-playercard-assets {ffdec : Ścieżka do pliku ffdec.jar}';

    protected $description = 'Generuje z plików SWF warstwy PNG używane przez playercardy pand';

    public function handle(PandaPlayercardAssetGenerator $generator): int
    {
        $result = $generator->generate((string) $this->argument('ffdec'), public_path('vendor/panfu-blog/playercard'));
        $this->info("Wygenerowano {$result['items']} warstw przedmiotów i {$result['colors']} wariantów kolorystycznych.");

        return self::SUCCESS;
    }
}
