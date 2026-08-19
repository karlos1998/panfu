<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Minigames\MinigameService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;

final class SocialHighscoreAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
        private readonly MinigameService $minigames,
    ) {}

    public function getSocialHighscore(int $playerId, int $otherPlayerId = -1): TypedObject
    {
        $current = $this->session->player();
        if ($current === null) {
            return $this->responses->make(1, valueObject: $this->valueObjects->make('List', ['list' => []]));
        }

        $player = User::query()->find($playerId) ?? $current;
        $other = $otherPlayerId > 0 && $otherPlayerId !== $player->getKey()
            ? User::query()->find($otherPlayerId)
            : null;

        return $this->responses->make(valueObject: $this->valueObjects->make('List', [
            'list' => $this->minigames->socialHighscores($player, $other),
        ]));
    }
}
