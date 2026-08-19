<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pets\PetService;
use App\Domain\Player\PlayerService;
use App\Domain\Player\PlayerStateService;
use App\Domain\Social\SocialService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;

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
        $player->update(['tour_finished' => (bool) $status]);

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

    public function purchaseItem(int $itemId, string $itemHash = ''): TypedObject
    {
        $player = $this->player();
        if ($player === null) {
            return $this->responses->make(1, 'Not logged in');
        }

        $result = $this->inventory->purchase($player, $itemId);

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

    /** @param list<int> $playerIds */
    public function getPlayerInfoList(array $playerIds, bool $detailed = false): TypedObject
    {
        if ($this->player() === null) {
            return $this->responses->make(1);
        }

        $list = User::query()->whereKey($playerIds)->get()
            ->map(fn (User $player): TypedObject => $this->players->info($player))
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
        return $this->responses->make();
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
            'furnitureList' => $this->inventory->furnitureFor($owner),
            'trackList' => [],
            'pets' => [],
            'pokoPets' => $this->pets->forPlayer($owner),
            'bollies' => [],
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
        if ($player !== null) {
            $player->update(['coins' => $score]);
        }

        return $this->responses->make();
    }

    private function player(): ?User
    {
        return $this->session->player();
    }
}
