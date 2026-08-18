<?php

namespace App\Console\Commands;

use App\Domain\Admin\Services\RoomDebugAssetGenerator;
use Illuminate\Console\Command;
use Throwable;

class GenerateRoomDebugAssets extends Command
{
    protected $signature = 'panfu:generate-room-debug-assets
        {ffdec : Ścieżka do pliku ffdec.jar}
        {--output= : Katalog wynikowy}';

    protected $description = 'Wyciąga z publicznych SWF dokładne warstwy walkarea dla debuggera pokoi';

    public function handle(RoomDebugAssetGenerator $generator): int
    {
        $output = $this->option('output') ?: public_path('vendor/panfu-admin/room-debug');

        try {
            $manifest = $generator->generate((string) $this->argument('ffdec'), (string) $output);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Wygenerowano {$manifest['generated']} warstw; bez walkarea: {$manifest['missing']}.");

        return self::SUCCESS;
    }
}
