<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Infrastructure\Amf\TypedObject;
use App\Models\WorldEventContainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final class WorldEventAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
    ) {}

    public function loadContainer(int $eventId): TypedObject
    {
        if ($this->session->player() === null || $eventId < 0) {
            return $this->responses->make(1);
        }

        return $this->responses->make(valueObject: $this->containerValue($this->container($eventId)));
    }

    public function increaseContainerValue(int $eventId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null || $eventId < 0) {
            return $this->responses->make(1);
        }

        $limiterKey = "amf-world-event:{$eventId}:{$player->getKey()}";
        if (RateLimiter::tooManyAttempts($limiterKey, 30)) {
            return $this->responses->make(1, 'World event contribution limit reached.');
        }
        RateLimiter::hit($limiterKey, 60);

        $this->container($eventId);

        $container = DB::transaction(function () use ($eventId): WorldEventContainer {
            $container = WorldEventContainer::query()->lockForUpdate()->findOrFail($eventId);
            $container->value = min((int) $container->max_value, (int) $container->value + 1);
            $container->save();

            return $container;
        });

        return $this->responses->make(valueObject: $this->containerValue($container));
    }

    private function container(int $eventId): WorldEventContainer
    {
        return WorldEventContainer::query()->firstOrCreate(
            ['id' => $eventId],
            ['value' => 0, 'max_value' => 1000],
        );
    }

    private function containerValue(WorldEventContainer $container): object
    {
        return (object) [
            'value' => (int) $container->value,
            'maxValue' => (int) $container->max_value,
        ];
    }
}
