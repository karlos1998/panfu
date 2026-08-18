<?php

namespace App\Console\Commands;

use App\Domain\Blog\Services\PandaAvatarAssetGenerator;
use Illuminate\Console\Command;

class GenerateBlogAvatarAssets extends Command
{
    protected $signature = 'panfu:generate-blog-avatars {ffdec : Ścieżka do pliku ffdec.jar}';

    protected $description = 'Generuje cache PNG awatarów bloga z warstw SWF aktywnego ekwipunku';

    public function handle(PandaAvatarAssetGenerator $generator): int
    {
        $result = $generator->generate((string) $this->argument('ffdec'), public_path('vendor/panfu-blog/playercard'));
        $this->info("Wygenerowano {$result['items']} warstw przedmiotów i {$result['colors']} wariantów kolorystycznych.");

        return self::SUCCESS;
    }
}
