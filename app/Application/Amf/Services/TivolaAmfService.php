<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\TivolaScore;

final class TivolaAmfService
{
    private const SUBJECTS = ['math', 'english', 'german', 'concentration', 'slot'];

    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
    ) {}

    public function loadScore(): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $score = TivolaScore::query()->firstOrCreate(['user_id' => $player->getKey()]);

        return $this->responses->make(valueObject: $this->valueObjects->make('TivolaScore', [
            'math' => (int) $score->math,
            'english' => (int) $score->english,
            'german' => (int) $score->german,
            'concentration' => (int) $score->concentration,
            'slot' => (int) $score->slot,
        ]));
    }

    public function updateScore(string $subject, int $score): TypedObject
    {
        $player = $this->session->player();
        $subject = strtolower(trim($subject));
        if ($player === null || ! in_array($subject, self::SUBJECTS, true) || $score < 0 || $score > 100_000_000) {
            return $this->responses->make(1);
        }
        $scores = TivolaScore::query()->firstOrCreate(['user_id' => $player->getKey()]);
        if ($score > (int) $scores->{$subject}) {
            $scores->forceFill([$subject => $score])->save();
        }

        return $this->loadScore();
    }
}
