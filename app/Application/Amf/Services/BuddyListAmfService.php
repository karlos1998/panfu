<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Social\SocialService;
use App\Infrastructure\Amf\TypedObject;

final class BuddyListAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly SocialService $social,
        private readonly PlayerSession $session,
    ) {}

    public function getCompleteBuddyList(int $userId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null || (int) $player->getKey() !== $userId) {
            return $this->responses->make(1, valueObject: $this->valueObjects->make('List', ['list' => []]));
        }

        return $this->responses->make(valueObject: $this->valueObjects->make('List', [
            'list' => $this->social->friendsFor($player),
        ]));
    }
}
