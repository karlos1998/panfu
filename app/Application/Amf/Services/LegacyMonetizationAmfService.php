<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Infrastructure\Amf\TypedObject;

final class LegacyMonetizationAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerSession $session,
    ) {}

    public function createHappyHourVoucherForVariant(int $variant): TypedObject
    {
        return $this->unavailable();
    }

    public function getMembershipCode(string $countryCode = ''): TypedObject
    {
        return $this->unavailable();
    }

    public function getSubscriptionCode(): TypedObject
    {
        return $this->unavailable();
    }

    public function getRewardProgress(): TypedObject
    {
        if ($this->session->player() === null) {
            return $this->responses->make(1);
        }

        // SponsorPay no longer exists. Returning a successful zero progress keeps
        // the legacy popup stable while preventing it from granting fake rewards.
        return $this->responses->make(valueObject: 0);
    }

    private function unavailable(): TypedObject
    {
        return $this->session->player() === null
            ? $this->responses->make(1)
            : $this->responses->make(1, 'This legacy payment provider is unavailable.');
    }
}
