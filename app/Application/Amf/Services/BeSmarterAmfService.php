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

        return $this->responses->make(valueObject: $this->result($score));
    }

    public function loadLeadingPlayer(): TypedObject
    {
        if ($this->session->player() === null) {
            return $this->responses->make(1);
        }

        $score = GameHighScore::query()
            ->with('player:id,name')
            ->where('game_id', self::GAME_ID)
            ->where('updated_at', '>=', now()->startOfMonth())
            ->orderByDesc('score')
            ->orderBy('completion_time')
            ->orderBy('updated_at')
            ->first();

        return $this->responses->make(valueObject: $score === null ? null : $this->result($score));
    }

    public function putScore(
        int $points,
        int $correctAnswers,
        int $falseAnswers,
        int $time,
        string $checksum,
    ): TypedObject {
        $player = $this->session->player();
        if (
            $player === null
            || $points !== $correctAnswers * 100
            || $correctAnswers < 0
            || $correctAnswers > 10
            || $falseAnswers < 0
            || $falseAnswers > 10
            || $correctAnswers + $falseAnswers > 10
            || $time < 0
            || $time > 310_000
            || preg_match('/^[a-f0-9]{32}$/iD', $checksum) !== 1
        ) {
            return $this->responses->make(1, 'Invalid Be Smarter result.');
        }

        $score = GameHighScore::query()->firstOrNew([
            'user_id' => $player->getKey(),
            'game_id' => self::GAME_ID,
        ]);
        if (! $score->exists || $points > (int) $score->score || ($points === (int) $score->score && $time < (int) ($score->completion_time ?? PHP_INT_MAX))) {
            $score->fill([
                'score' => $points,
                'correct_answers' => $correctAnswers,
                'false_answers' => $falseAnswers,
                'completion_time' => $time,
            ])->save();
        }

        return $this->responses->make(valueObject: $this->result($score->refresh()));
    }

    private function result(GameHighScore $score): object
    {
        $player = $score->relationLoaded('player') ? $score->player : User::query()->find($score->user_id);

        return (object) [
            'correctAnswers' => (int) ($score->correct_answers ?? 0),
            'playerName' => (string) $player?->name,
            'playerId' => (int) $score->user_id,
            'points' => (int) $score->score,
            'falseAnswers' => (int) ($score->false_answers ?? 0),
            'time' => (int) ($score->completion_time ?? 0),
        ];
    }
}
