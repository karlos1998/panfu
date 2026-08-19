<?php

namespace App\Domain\Minigames;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GameHighScore;
use App\Models\User;
use Illuminate\Support\Carbon;

final class MinigameService
{
    /** @var list<int> */
    private const SOCIAL_GAME_IDS = [
        4, 5, 6, 7, 10, 11, 12, 13, 15, 16, 17, 18, 19, 20, 21, 23,
        24, 25, 26, 27, 28, 29, 31, 32, 33, 34, 35, 36, 37, 38, 40, 41,
        42, 44, 45, 46, 47, 48, 49, 50, 51, 52, 55, 56,
    ];

    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    public function recordBest(User $player, int $gameId, int $score): void
    {
        if ($gameId <= 0) {
            return;
        }

        $score = max(0, $score);
        $entry = GameHighScore::query()->firstOrNew([
            'user_id' => $player->getKey(),
            'game_id' => $gameId,
        ]);
        if (! $entry->exists || $score > (int) $entry->score) {
            $entry->score = $score;
            $entry->save();
        }
    }

    public function highscoreLists(int $gameId): TypedObject
    {
        return $this->valueObjects->make('GameHighScores', [
            'id' => $gameId,
            'dailyHighscores' => $this->ranked($gameId, now()->subDay()),
            'weeklyHighscores' => $this->ranked($gameId, now()->subWeek()),
            'overAllHighscores' => $this->ranked($gameId),
        ]);
    }

    /** @return list<TypedObject> */
    public function socialHighscores(User $player, ?User $other): array
    {
        $playerScores = $this->scoresFor($player);
        $otherScores = $other === null ? [] : $this->scoresFor($other);

        return array_map(fn (int $gameId): TypedObject => $this->valueObjects->make('SocialHighscore', [
            'gameID' => $gameId,
            'playerID' => (int) $player->getKey(),
            'otherPlayerID' => $other === null ? -1 : (int) $other->getKey(),
            'playerScore' => $playerScores[$gameId] ?? 0,
            'otherPlayerScore' => $otherScores[$gameId] ?? 0,
        ]), self::SOCIAL_GAME_IDS);
    }

    /** @return list<TypedObject> */
    private function ranked(int $gameId, ?Carbon $since = null): array
    {
        return GameHighScore::query()
            ->with('player:id,name')
            ->where('game_id', $gameId)
            ->when($since, fn ($query) => $query->where('updated_at', '>=', $since))
            ->orderByDesc('score')
            ->orderBy('updated_at')
            ->orderBy('user_id')
            ->limit(5)
            ->get()
            ->values()
            ->map(fn (GameHighScore $entry, int $index): TypedObject => $this->valueObjects->make('HighScoreEntry', [
                'ranking' => $index + 1,
                'playerID' => (string) $entry->user_id,
                'playerName' => (string) $entry->player?->name,
                'score' => (int) $entry->score,
            ]))
            ->all();
    }

    /** @return array<int, int> */
    private function scoresFor(User $player): array
    {
        return $player->gameHighScores()
            ->pluck('score', 'game_id')
            ->mapWithKeys(fn (mixed $score, mixed $gameId): array => [(int) $gameId => (int) $score])
            ->all();
    }
}
