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

    /** @param list<int> $buddyIds */
    public function getBuddyList(array $buddyIds): TypedObject
    {
        $player = $this->session->player();

        return $this->responses->make(
            $player === null ? 1 : 0,
            valueObject: $this->valueObjects->make('List', [
                'list' => $player === null ? [] : $this->social->buddiesByIds($player, $buddyIds),
            ]),
        );
    }

    public function changeBestFriend(int $oldBuddyId = 0, int $newBuddyId = 0): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        return $this->social->changeBestFriend($player, $oldBuddyId, $newBuddyId)
            ? $this->responses->make()
            : $this->responses->make(1, 'The selected panda is not a buddy.');
    }
}
