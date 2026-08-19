<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Infrastructure\Amf\TypedObject;

final class BuddyFilterAmfService
{
    public function __construct(private readonly AmfResponseFactory $responses) {}

    public function listFilteredBuddies(): TypedObject
    {
        return $this->responses->make();
    }
}
