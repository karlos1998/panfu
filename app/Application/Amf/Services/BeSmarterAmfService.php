<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GameHighScore;
use App\Models\User;

final class BeSmarterAmfService
{
    private const GAME_ID = 51;

    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
    ) {}

    public function loadBestResult(int $playerId): TypedObject
    {
        if ($this->session->player() === null) {
            return $this->responses->make(1);
        }

        $player = User::query()->find($playerId);
        if ($player === null) {
            return $this->responses->make();
        }

        $score = GameHighScore::query()
            ->where('user_id', $player->getKey())
            ->where('game_id', self::GAME_ID)
            ->first();

        if ($score === null) {
            return $this->responses->make();
        }

        return $this->responses->make(valueObject: (object) [
            'correctAnswers' => 0,
            'playerName' => (string) $player->name,
            'playerId' => (int) $player->getKey(),
            'points' => (int) $score->score,
            'falseAnswers' => 0,
            'time' => 0,
        ]);
    }
}
