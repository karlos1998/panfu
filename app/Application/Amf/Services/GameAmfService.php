<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Domain\Minigames\MinigameService;
use App\Infrastructure\Amf\TypedObject;

final class GameAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
        private readonly MinigameService $minigames,
    ) {}

    public function setHighScore(int $gameId, int $score): TypedObject
    {
        return $this->record($gameId, $score);
    }

    public function finishMinigame(int $gameId, int $score): TypedObject
    {
        return $this->record($gameId, $score);
    }

    public function getHighScoreLists(int $gameId): TypedObject
    {
        return $this->responses->make(valueObject: $this->minigames->highscoreLists($gameId));
    }

    private function record(int $gameId, int $score): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        if (! $this->minigames->recordBest($player, $gameId, $score)) {
            return $this->responses->make(1, 'Invalid minigame score.');
        }

        return $this->responses->make();
    }
}
