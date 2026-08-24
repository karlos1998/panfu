<?php

namespace App\Domain\Quests;

use SimpleXMLElement;

final class QuestRewardCatalog
{
    /** @var array<int, list<string>>|null */
    private ?array $rewardChecks = null;

    public function isReward(int $itemId, string $check): bool
    {
        $check = trim($check);

        if ($itemId <= 0 || $check === '') {
            return false;
        }

        return in_array($check, $this->rewardChecks()[$itemId] ?? [], true);
    }

    /** @return array<int, list<string>> */
    private function rewardChecks(): array
    {
        if ($this->rewardChecks !== null) {
            return $this->rewardChecks;
        }

        $this->rewardChecks = [];

        foreach (glob(public_path('vendor/openpanfu/quests/*/conf/*.xml')) ?: [] as $configurationPath) {
            $configuration = simplexml_load_file($configurationPath);
            if (! $configuration instanceof SimpleXMLElement) {
                continue;
            }

            foreach ($configuration->xpath('//items/item') ?: [] as $item) {
                $itemId = (int) $item['itemID'];
                $check = trim((string) ($item['hash'] ?: $item['check']));

                if ($itemId <= 0 || $check === '') {
                    continue;
                }

                $this->rewardChecks[$itemId] ??= [];
                $this->rewardChecks[$itemId][] = $check;
            }
        }

        return $this->rewardChecks;
    }
}
