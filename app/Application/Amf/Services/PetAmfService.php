<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Domain\Pets\PetService;
use App\Domain\Servers\GameServerService;
use App\Infrastructure\Amf\TypedObject;

final class PetAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
        private readonly PetService $pets,
        private readonly GameServerService $servers,
    ) {}

    public function buyPet(int $type, string $name): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1, 'Not logged in.');
        }

        $reservedAt = (int) $this->session->get('pokopet_voucher_reserved_at', 0);
        $result = $this->pets->buy($player, $type, $name, $reservedAt >= time() - 300);
        if ($type === 5) {
            $this->session->forget('pokopet_voucher_reserved_at');
        }

        return $this->responses->make($result['statusCode'], $result['message'], $result['pet']);
    }

    public function switchPet(int $petId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        return $this->pets->select($player, $petId)
            ? $this->responses->make()
            : $this->responses->make(3, 'Pokopet not found.');
    }

    public function updatePetState(int $petId, string $state): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $pet = $this->pets->updateState($player, $petId, $state);

        return $pet === null
            ? $this->responses->make(3, 'Pokopet not found or state is invalid.')
            : $this->responses->make(valueObject: $pet);
    }

    public function removePet(int $petId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        return $this->pets->remove($player, $petId)
            ? $this->responses->make()
            : $this->responses->make(3, 'Pokopet not found.');
    }

    public function feed(int $petId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $health = $this->pets->feed($player, $petId);

        return $health === null
            ? $this->responses->make(3, 'Pokopet not found.')
            : $this->responses->make(valueObject: $health);
    }

    public function increaseHealth(): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $pet = $this->pets->increaseSelectedHealth($player);

        return $pet === null
            ? $this->responses->make(3, 'No selected Pokopet can recover health.')
            : $this->responses->make(valueObject: $pet);
    }

    public function getGameServer(): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $server = $this->servers->selectedFor((int) ($player->current_gameserver ?? 0));

        return $server === null
            ? $this->responses->make(3, 'No game server is available.')
            : $this->responses->make(valueObject: $server);
    }
}
