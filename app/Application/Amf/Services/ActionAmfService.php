<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Player\ProgressionService;
use App\Infrastructure\Amf\TypedObject;

final class ActionAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
        private readonly ProgressionService $progression,
    ) {}

    public function getLastDoneActionToday(mixed $id, string $action, mixed $time): ?TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return null;
        }

        return $this->responses->make(
            message: $action,
            valueObject: $this->valueObjects->make('UserActionDaily', ['playerId' => (int) $player->getKey()]),
        );
    }

    public function performAction(int $playerId, string $action): ?TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return null;
        }

        $response = $this->responses->make();
        if ((int) $player->getKey() !== $playerId || $action !== 'played10') {
            return $response;
        }

        $last = (int) $this->session->get('last_played_10', 0);
        $elapsed = time() - $last;
        if ($elapsed < $this->progression->cooldownSeconds()) {
            return $this->responses->make(1, "lastplayed10 denied {$elapsed} seconds since last request.");
        }

        $this->session->put('last_played_10', time());

        return $this->responses->make(
            message: "lastplayed10 accepted {$elapsed} seconds since last request.",
            valueObject: $this->progression->rewardPlaytime($player),
        );
    }
}
