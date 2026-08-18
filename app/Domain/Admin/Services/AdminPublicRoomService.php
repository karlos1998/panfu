<?php

namespace App\Domain\Admin\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

class AdminPublicRoomService
{
    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $catalog = null;

    /** @param array{search: string, status: string, sort: string} $filters */
    public function paginatedRooms(array $filters): array
    {
        $rooms = $this->rooms()
            ->when($filters['search'] !== '', function (Collection $rooms) use ($filters): Collection {
                $search = Str::lower($filters['search']);

                return $rooms->filter(fn (array $room) => Str::contains(Str::lower("{$room['id']} {$room['key']} {$room['number']}"), $search));
            })
            ->when($filters['status'] === 'allowed', fn (Collection $rooms) => $rooms->where('allowed', true))
            ->when($filters['status'] === 'disabled', fn (Collection $rooms) => $rooms->where('allowed', false))
            ->when($filters['status'] === 'collision', fn (Collection $rooms) => $rooms->where('restrictToWalkArea', true))
            ->when($filters['status'] === 'vehicle', fn (Collection $rooms) => $rooms->where('vehicleArea', true))
            ->when($filters['status'] === 'missing', fn (Collection $rooms) => $rooms->where('assetExists', false));

        $rooms = $filters['sort'] === 'name'
            ? $rooms->sortBy('id', SORT_NATURAL | SORT_FLAG_CASE)
            : $rooms->sortBy('number');

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $paginator = new LengthAwarePaginator(
            $rooms->forPage($page, $perPage)->values(),
            $rooms->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()],
        );

        return ['rooms' => $paginator, 'filters' => $filters];
    }

    /** @return array<string, mixed> */
    public function roomDetails(string $roomId): array
    {
        $room = $this->rooms()->firstWhere('id', $roomId);

        if ($room === null) {
            abort(404);
        }

        $configPath = public_path("vendor/openpanfu/rooms/{$roomId}/conf/{$roomId}.xml");
        $config = is_file($configPath) ? simplexml_load_file($configPath) : null;
        $debug = $this->debugManifest()[$roomId] ?? ['walkAreaCharacterId' => null, 'walkAreaFrames' => [], 'markers' => []];

        return [
            'room' => [
                ...$room,
                'spawns' => $room['spawns'],
                'assets' => $this->assets($roomId, $config),
                'sounds' => $this->sounds($roomId, $config),
                'dates' => $this->dates($config),
                'elements' => $this->elements($config),
                'hotspots' => $this->hotspots($config),
                'debug' => $debug,
            ],
            'client' => [
                'ruffleScript' => '/vendor/ruffle/ruffle.js',
                'stageWidth' => RoomDebugAssetGenerator::STAGE_WIDTH,
                'stageHeight' => RoomDebugAssetGenerator::STAGE_HEIGHT,
            ],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rooms(): Collection
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $config = simplexml_load_file(public_path('vendor/openpanfu/conf/config.xml'));

        if (! $config instanceof SimpleXMLElement) {
            throw new RuntimeException('Nie udało się wczytać katalogu publicznych pokoi.');
        }

        return $this->catalog = collect(iterator_to_array($config->rooms->room, false))
            ->filter(fn (SimpleXMLElement $room) => (string) $room['id'] !== 'home')
            ->map(function (SimpleXMLElement $room): array {
                $id = (string) $room['id'];
                $assetPath = public_path("vendor/openpanfu/rooms/{$id}/assets/room.swf");
                $configPath = public_path("vendor/openpanfu/rooms/{$id}/conf/{$id}.xml");

                return [
                    'id' => $id,
                    'number' => (int) $room['num'],
                    'key' => (string) $room['key'],
                    'label' => Str::headline($id),
                    'allowed' => (string) $room['allowed'] === '1',
                    'restrictToWalkArea' => (string) $room['dontAllowWalkOutWalkArea'] === 'true',
                    'vehicleArea' => (string) $room['areaForVehicle'] === 'true',
                    'jumping' => ! isset($room['jumping']) || (string) $room['jumping'] !== 'false',
                    'volume' => (float) ($room['volume'] ?: 1),
                    'assetExists' => is_file($assetPath),
                    'configExists' => is_file($configPath),
                    'assetSize' => is_file($assetPath) ? filesize($assetPath) : null,
                    'roomSwfUrl' => is_file($assetPath) ? "/vendor/openpanfu/rooms/{$id}/assets/room.swf" : null,
                    'spawns' => collect(iterator_to_array($room->spawn, false))->map(fn (SimpleXMLElement $spawn) => [
                        'from' => (string) $spawn['from'],
                        'x' => (int) $spawn['x'],
                        'y' => (int) $spawn['y'],
                        'radiusX' => (int) $spawn['radx'],
                        'radiusY' => (int) $spawn['rady'],
                        'rotation' => isset($spawn['rot']) ? (int) $spawn['rot'] : null,
                    ])->values()->all(),
                ];
            })
            ->values();
    }

    /** @return array<int, array<string, mixed>> */
    private function assets(string $roomId, ?SimpleXMLElement $config): array
    {
        return $this->nodes($config?->assets->asset)->map(function (SimpleXMLElement $asset) use ($roomId): array {
            $path = (string) $asset['path'];
            $absolutePath = public_path("vendor/openpanfu/rooms/{$roomId}/{$path}");

            return [
                'id' => (string) $asset['id'],
                'path' => $path,
                'preload' => ! isset($asset['preload']) || (string) $asset['preload'] !== '0',
                'exists' => ! str_contains($path, '$$') && is_file($absolutePath),
                'url' => ! str_contains($path, '$$') && is_file($absolutePath) ? "/vendor/openpanfu/rooms/{$roomId}/{$path}" : null,
            ];
        })->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function sounds(string $roomId, ?SimpleXMLElement $config): array
    {
        return $this->nodes($config?->sounds->sound)->map(function (SimpleXMLElement $sound) use ($roomId): array {
            $path = (string) $sound['path'];
            $absolutePath = public_path("vendor/openpanfu/rooms/{$roomId}/{$path}");

            return [
                'id' => (string) $sound['id'],
                'path' => $path,
                'volume' => isset($sound['volume']) ? (float) $sound['volume'] : null,
                'loops' => isset($sound['loops']) ? (int) $sound['loops'] : null,
                'exists' => ! str_contains($path, '$$') && is_file($absolutePath),
            ];
        })->values()->all();
    }

    /** @return array<int, array<string, string>> */
    private function dates(?SimpleXMLElement $config): array
    {
        return $this->nodes($config?->dates->date)->map(fn (SimpleXMLElement $date) => [
            'id' => (string) $date['id'],
            'start' => (string) $date['start'],
            'finish' => (string) $date['finish'],
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function elements(?SimpleXMLElement $config): array
    {
        return $this->nodes($config?->module->elements->element)->map(function (SimpleXMLElement $element): array {
            $messages = [];

            foreach ($element->message as $message) {
                $messages[] = (string) $message['value'];
            }

            return [
                'id' => (string) ($element['id'] ?: $element['type']),
                'type' => isset($element['type']) ? (string) $element['type'] : null,
                'button' => (string) $element['button'] === '1',
                'visible' => ! isset($element['visible']) || (string) $element['visible'] === '1',
                'messages' => array_values(array_filter($messages)),
            ];
        })->values()->all();
    }

    /** @return array<int, array<string, string>> */
    private function hotspots(?SimpleXMLElement $config): array
    {
        $hotspots = [];

        foreach ($config?->module->elements->element ?? [] as $element) {
            foreach ($element->message as $message) {
                if ((string) $message['value'] !== 'gotoHotSpot') {
                    continue;
                }

                $hotspots[] = [
                    'element' => (string) ($element['id'] ?: $element['type']),
                    'target' => (string) $message->hotSpot['value'],
                ];
            }
        }

        return $hotspots;
    }

    /** @return array<string, mixed> */
    private function debugManifest(): array
    {
        $path = public_path('vendor/panfu-admin/room-debug/manifest.json');

        if (! is_file($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        return is_array($manifest['rooms'] ?? null) ? $manifest['rooms'] : [];
    }

    /** @return Collection<int, SimpleXMLElement> */
    private function nodes(?SimpleXMLElement $nodes): Collection
    {
        return collect($nodes === null ? [] : iterator_to_array($nodes, false));
    }
}
