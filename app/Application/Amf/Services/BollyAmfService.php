<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Domain\Pets\BollyService;
use App\Infrastructure\Amf\TypedObject;

final class BollyAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
        private readonly BollyService $bollies,
    ) {}

    public function purchaseBolly(int $definitionId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $result = $this->bollies->purchase($player, $definitionId);

        return $this->responses->make($result['statusCode'], $result['message'], $result['bolly']);
    }

    public function removeBolly(int $definitionId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        return $this->bollies->remove($player, $definitionId)
            ? $this->responses->make()
            : $this->responses->make(3, 'Bolly not found.');
    }

    public function updateBolly(TypedObject $bolly): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        $updated = $this->bollies->update($player, $bolly);

        return $updated === null
            ? $this->responses->make(3, 'Bolly not found or state is invalid.')
            : $this->responses->make(valueObject: $updated);
    }
}
