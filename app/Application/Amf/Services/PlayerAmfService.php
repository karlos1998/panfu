<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pets\BollyService;
use App\Domain\Pets\PetService;
use App\Domain\Player\PlayerService;
use App\Domain\Player\PlayerStateService;
use App\Domain\Quests\QuestRewardCatalog;
use App\Domain\Social\SocialService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\PlayerReport;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

final class PlayerAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
        private readonly PlayerService $players,
        private readonly PlayerStateService $states,
        private readonly InventoryService $inventory,
        private readonly SocialService $social,
        private readonly PetService $pets,
        private readonly BollyService $bollies,
        private readonly QuestRewardCatalog $questRewards,
    ) {}

    /** @param list<int> $categories */
    public function getStates(array $categories): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        return $this->responses->make(valueObject: $this->valueObjects->make('List', [
            'list' => $this->states->get($player, $categories),
        ]));
    }

    public function setState(int $category, int $name, int $value): TypedObject
    {
        $player = $this->player();

        return $player === null
            ? $this->responses->make(1)
            : $this->responses->make(valueObject: $this->states->set($player, $category, $name, $value));
    }

    public function updateTourFinished(int|bool $status): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $player->forceFill(['tour_finished' => (bool) $status])->save();

        return $this->responses->make(message: 'Tour updated!');
    }

    public function addToBuddylist(int $buddyId): TypedObject
    {
        $player = $this->player();
        $buddy = User::query()->find($buddyId);
        if ($player !== null && $buddy !== null && ! $player->is($buddy)) {
            $this->social->addFriends($player, $buddy);
        }

        return $this->responses->make();
    }

    public function removeFromBuddyList(int $buddyId): TypedObject
    {
        $player = $this->player();
        $buddy = User::query()->find($buddyId);
        if ($player === null || $buddy === null) {
            return $this->responses->make(1);
        }

        $this->social->removeFriends($player, $buddy);

        return $this->responses->make();
    }

    public function purchaseItem(int $itemId, string $itemHash = ''): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1, 'Not logged in');
        }

        $result = $this->inventory->purchase(
            $player,
            $itemId,
            $this->questRewards->isReward($itemId, $itemHash),
        );

        return $this->responses->make($result['statusCode'], $result['message'], $result['valueObject']);
    }

    /** @param list<mixed> $activeInventory @param list<mixed> $inactiveInventory */
    public function updateItems(array $activeInventory, array $inactiveInventory): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $this->inventory->updateEquipped($player, $activeInventory, $inactiveInventory);

        return $this->responses->make(valueObject: $this->players->info($player->refresh()));
    }

    /** @param list<int> $itemIds */
    public function removeItems(array $itemIds): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        $remove = [];
        foreach ($itemIds as $itemId) {
            if ((int) $itemId === 101830 && $this->inventory->has($player, 101830)) {
                $this->session->put('pokopet_voucher_reserved_at', time());
            } else {
                $remove[] = (int) $itemId;
            }
        }
        $this->inventory->remove($player, $remove);

        return $this->responses->make(valueObject: $this->valueObjects->make('Inventory', [
            'activeItems' => $this->inventory->itemsFor($player, true),
            'inactiveItems' => $this->inventory->itemsFor($player, false),
        ]));
    }

    public function removeItem(int $itemId): TypedObject
    {
        return $this->removeItems([$itemId]);
    }

    /** @param list<int> $playerIds */
    public function getPlayerInfoList(array $playerIds, bool $detailed = false): TypedObject
    {
        if ($this->player() === null) {
            return $this->responses->make(1);
        }

        $playerIds = array_slice(array_values(array_unique(array_map('intval', $playerIds))), 0, 100);
        $players = User::query()->whereKey($playerIds)->get()->keyBy(
            fn (User $player): int => (int) $player->getKey(),
        );
        $list = collect($playerIds)
            ->map(fn (int $playerId): ?User => $players->get($playerId))
            ->filter()
            ->map(fn (User $player): TypedObject => $this->players->info($player))
            ->values()
            ->all();

        return $this->responses->make(valueObject: $this->valueObjects->make('List', ['list' => $list]));
    }

    /** @param list<int> $playerIds */
    public function getSmallPlayerInfoList(array $playerIds): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        $ids = array_slice(array_values(array_unique(array_map('intval', $playerIds))), 0, 100);
        $players = User::query()->whereKey($ids)->get()->keyBy('id');
        $list = collect($ids)
            ->map(fn (int $id): ?User => $players->get($id))
            ->filter()
            ->map(fn (User $entry): TypedObject => $this->valueObjects->make('SmallPlayerInfo', [
                'playerId' => (int) $entry->getKey(),
                'playerName' => (string) $entry->name,
                'currentGameServer' => (int) ($entry->current_gameserver ?? 0),
            ]))
            ->values()
            ->all();

        return $this->responses->make(valueObject: $this->valueObjects->make('List', ['list' => $list]));
    }

    public function getPlayerCard(int $playerId): TypedObject
    {
        $player = $this->player();
        $requested = User::query()->find($playerId);

        return $this->responses->make(
            valueObject: $player !== null && $requested !== null ? $this->players->info($requested) : null,
        );
    }

    public function lockHome(bool $locked): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $player->forceFill(['home_locked' => $locked])->save();

        return $this->responses->make(valueObject: $locked);
    }

    public function getPlayerHome(int $playerId): TypedObject
    {
        if ($this->player() === null) {
            return $this->responses->make();
        }
        $owner = User::query()->find($playerId);
        if ($owner === null) {
            return $this->responses->make(1, 'Error occured while getting your inventory.');
        }

        return $this->responses->make(valueObject: $this->valueObjects->make('HomeData', [
            'playerID' => $playerId,
            'locked' => (bool) $owner->home_locked,
            'furnitureList' => $this->inventory->furnitureFor($owner),
            'trackList' => [],
            'pets' => [],
            'pokoPets' => $this->pets->forPlayer($owner),
            'bollies' => $this->bollies->forPlayer($owner),
        ]));
    }

    /** @param list<mixed> $furniture */
    public function updateFurnitures(array $furniture): TypedObject
    {
        $player = $this->player();
        if ($player !== null) {
            $this->inventory->updateFurniture($player, $furniture);
        }

        return $this->responses->make($player === null ? 1 : 0);
    }

    public function updateScore(int $score): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        $limiterKey = 'amf-coin-update:'.$player->getKey();
        $limit = max(1, (int) config('panfu.amf.coin_updates_per_minute', 10));
        if (RateLimiter::tooManyAttempts($limiterKey, $limit)) {
            return $this->responses->make(1, 'Coin balance update rejected.');
        }
        RateLimiter::hit($limiterKey, 60);

        return $this->players->updateCoinBalance($player, $score)
            ? $this->responses->make()
            : $this->responses->make(1, 'Coin balance update rejected.');
    }

    public function reportPlayer(int $reportedId, string $reason): TypedObject
    {
        $reporter = $this->player();
        $reported = User::query()->find($reportedId);
        $reason = trim(strip_tags($reason));
        if ($reporter === null || $reported === null || $reporter->is($reported) || $reason === '') {
            return $this->responses->make(1);
        }

        PlayerReport::query()->create([
            'reporter_id' => $reporter->getKey(),
            'reported_id' => $reported->getKey(),
            'reason' => mb_substr($reason, 0, 255),
        ]);

        return $this->responses->make(message: (string) $reported->getKey());
    }

    public function updateHelperStatus(bool $enabled): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $player->forceFill(['helper_status' => $enabled])->save();

        return $this->responses->make(valueObject: $enabled);
    }

    public function updatePlayerState(int $playerId, string $state): TypedObject
    {
        $player = $this->player();
        $state = trim($state);
        if ($player === null || (int) $player->getKey() !== $playerId || strlen($state) > 80) {
            return $this->responses->make(1);
        }
        $player->forceFill(['player_state' => $state])->save();

        return $this->responses->make();
    }

    private function player(): ?User
    {
        return $this->session->player();
    }
}
