<?php

namespace App\Domain\Player;

use App\Application\Amf\ValueObjectFactory;
use App\Domain\Inventory\InventoryService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\Item;
use App\Models\User;

final class ProgressionService
{
    /** @var array<string, mixed>|null */
    private ?array $definitions = null;

    public function __construct(
        private readonly ValueObjectFactory $valueObjects,
        private readonly InventoryService $inventory,
    ) {}

    public function cooldownSeconds(): int
    {
        return max(1, (int) config('panfu.leveling.tick_seconds', 600) - 20);
    }

    public function rewardPlaytime(User $player): TypedObject
    {
        $rewards = [];
        $currentLevel = (int) ($player->social_level ?? 1);
        $maximum = min((int) ($this->definitions()['maxLevel'] ?? 60), (int) config('panfu.leveling.max_level', 60));
        if ($currentLevel >= $maximum) {
            return $this->valueObjects->make('List', ['list' => []]);
        }

        $newScore = (int) ($player->social_score ?? 0) + $this->incrementFor($currentLevel);
        $levelReward = $this->valueObjects->make('Reward', ['type' => 'sp']);

        if ($newScore >= 100) {
            $newScore -= 100;
            $newLevel = min($currentLevel + 1, $maximum);
            if ($newLevel >= $maximum) {
                $newScore = 0;
            }
            $levelReward->set('levelStatus', 1)->set('number', $newScore);
            $rewards[] = $levelReward;

            foreach ($this->level($newLevel)['rewards'] ?? [] as $definition) {
                $reward = $this->valueObjects->make('Reward', ['type' => (string) ($definition['type'] ?? '')]);
                if (($definition['type'] ?? null) === 'item') {
                    $itemId = (int) ($definition['value'] ?? 0);
                    $entry = $this->inventory->add($player, $itemId);
                    $item = Item::query()->find($itemId);
                    if ($item !== null) {
                        $reward->set('item', $this->inventory->itemValueObject($item, $entry));
                    }
                } elseif (($definition['type'] ?? null) === 'score') {
                    $reward->set('number', (int) ($definition['value'] ?? 0));
                }
                $rewards[] = $reward;
            }
            $player->social_level = $newLevel;
        } else {
            $levelReward->set('number', min(99, $newScore));
            $rewards[] = $levelReward;
        }

        $player->social_score = min(99, $newScore);
        $player->save();

        return $this->valueObjects->make('List', ['list' => $rewards]);
    }

    /** @return array<string, mixed> */
    private function definitions(): array
    {
        if ($this->definitions === null) {
            $contents = file_get_contents(resource_path('data/game/levels.json')) ?: '{}';
            $this->definitions = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        }

        return $this->definitions;
    }

    /** @return array<string, mixed> */
    private function level(int $level): array
    {
        foreach ($this->definitions()['levels'] ?? [] as $definition) {
            if ((int) ($definition['level'] ?? 0) === $level) {
                return $definition;
            }
        }

        return [];
    }

    private function incrementFor(int $level): int
    {
        $baseMinutes = max(1.0, (float) config('panfu.leveling.base_minutes', 10));
        $growth = max(0.0, (float) config('panfu.leveling.growth_rate', 0.10));
        $tickMinutes = max(1, (int) config('panfu.leveling.tick_seconds', 600)) / 60;
        $requiredMinutes = $baseMinutes * pow(1 + $growth, max(0, $level - 1));

        return max(1, (int) round(100 * ($tickMinutes / $requiredMinutes)));
    }
}
