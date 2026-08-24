<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Social\SocialService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;

final class BuddyFilterAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
        private readonly SocialService $social,
    ) {}

    public function listFilteredBuddies(): TypedObject
    {
        $player = $this->session->player();

        return $this->responses->make(
            $player === null ? 1 : 0,
            valueObject: $this->valueObjects->make('List', [
                'list' => $player === null ? [] : $this->social->blockedRelationsFor($player),
            ]),
        );
    }

    public function addFilteredBuddy(int $playerId, int $blockedId, int $level = 1): TypedObject
    {
        $player = $this->session->player();
        $blocked = User::query()->find($blockedId);
        if ($player === null || (int) $player->getKey() !== $playerId || $blocked === null || $player->is($blocked)) {
            return $this->responses->make(1);
        }

        $this->social->block($player, $blocked, $level);

        return $this->responses->make(valueObject: $this->valueObjects->make('BuddyFilter', [
            'buddy1' => $playerId,
            'buddy2' => $blockedId,
            'level' => max(1, $level),
        ]));
    }

    public function removeFilteredBuddy(int $playerId, int $blockedId): TypedObject
    {
        $player = $this->session->player();
        $blocked = User::query()->find($blockedId);
        if ($player === null || (int) $player->getKey() !== $playerId || $blocked === null) {
            return $this->responses->make(1);
        }

        $this->social->unblock($player, $blocked);

        return $this->responses->make(valueObject: $this->valueObjects->make('BuddyFilter', [
            'buddy1' => $playerId,
            'buddy2' => $blockedId,
            'level' => 1,
        ]));
    }
}
