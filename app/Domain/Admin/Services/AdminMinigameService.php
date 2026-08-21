<?php

namespace App\Domain\Admin\Services;

use App\Models\MinigameReward;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

class AdminMinigameService
{
    /** @return array<string, mixed> */
    public function catalog(): array
    {
        $names = $this->names();
        $definitions = $this->definitions();
        $rooms = $this->rooms();
        $minigames = MinigameReward::query()
            ->orderBy('game_id')
            ->get()
            ->map(function (MinigameReward $reward) use ($names, $definitions, $rooms): array {
                $gameId = $reward->game_id;
                $thumbnailPath = public_path("vendor/panfu-admin/minigames/game{$gameId}.webp");
                $swfPath = public_path("vendor/openpanfu/swf/games/game{$gameId}.swf");
                $definition = $definitions[$gameId] ?? null;

                return [
                    'id' => $gameId,
                    'name' => $names[$gameId] ?? "Minigra #{$gameId}",
                    'type' => $definition['type'] ?? null,
                    'adapter' => $definition['adapter'] ?? null,
                    'enabled' => $reward->enabled,
                    'coinMultiplier' => $reward->coin_multiplier,
                    'maxCoinsPerRound' => $reward->max_coins_per_round,
                    'rooms' => $rooms[$gameId] ?? [],
                    'thumbnailUrl' => is_file($thumbnailPath)
                        ? "/vendor/panfu-admin/minigames/game{$gameId}.webp"
                        : null,
                    'swfUrl' => is_file($swfPath)
                        ? "/vendor/openpanfu/swf/games/game{$gameId}.swf"
                        : null,
                ];
            });

        return [
            'minigames' => $minigames,
            'metrics' => [
                'total' => $minigames->count(),
                'enabled' => $minigames->where('enabled', true)->count(),
                'customMultiplier' => $minigames
                    ->where('coinMultiplier', '!=', '0.0500')
                    ->count(),
            ],
        ];
    }

    /** @return array<int, string> */
    private function names(): array
    {
        $snippets = $this->loadXml(public_path('vendor/openpanfu/conf/snippets/text_snippets_client_PL.xml'));
        $names = [];

        foreach ($snippets->children() as $snippet) {
            if (preg_match('/^GAME_NAME_(\d+)$/', $snippet->getName(), $matches) !== 1) {
                continue;
            }

            $name = trim((string) $snippet);

            if ($name !== '') {
                $names[(int) $matches[1]] = $name;
            }
        }

        return $names;
    }

    /** @return array<int, array{type: string, adapter: string}> */
    private function definitions(): array
    {
        $config = $this->loadXml(public_path('vendor/openpanfu/conf/config.xml'));
        $definitions = [];

        foreach ($config->games->game as $game) {
            $definitions[(int) $game['id']] = [
                'type' => (string) $game['type'],
                'adapter' => (string) $game['adapter'],
            ];
        }

        return $definitions;
    }

    /** @return array<int, array<int, array{id: string, number: int, label: string, allowed: bool}>> */
    private function rooms(): array
    {
        $config = $this->loadXml(public_path('vendor/openpanfu/conf/config.xml'));
        $rooms = [];

        foreach ($config->rooms->room as $room) {
            $roomId = (string) $room['id'];
            $roomConfigPath = public_path("vendor/openpanfu/rooms/{$roomId}/conf/{$roomId}.xml");

            if (! is_file($roomConfigPath)) {
                continue;
            }

            $roomConfig = $this->loadXml($roomConfigPath);

            foreach ($roomConfig->xpath('//tooltip[@type="game"]') ?: [] as $tooltip) {
                if (preg_match('/^GAME_NAME_(\d+)$/', (string) $tooltip['name'], $matches) !== 1) {
                    continue;
                }

                $rooms[(int) $matches[1]][] = [
                    'id' => $roomId,
                    'number' => (int) $room['num'],
                    'label' => Str::headline($roomId),
                    'allowed' => (string) $room['allowed'] === '1',
                ];
            }
        }

        foreach ($rooms as &$gameRooms) {
            usort($gameRooms, fn (array $left, array $right): int => $left['number'] <=> $right['number']);
        }
        unset($gameRooms);

        return $rooms;
    }

    private function loadXml(string $path): SimpleXMLElement
    {
        $xml = simplexml_load_file($path);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException("Nie udało się wczytać pliku XML: {$path}");
        }

        return $xml;
    }
}
