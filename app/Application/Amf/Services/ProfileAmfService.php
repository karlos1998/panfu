<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;

final class ProfileAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
    ) {}

    public function getProfile(int $id, bool $premium = false): TypedObject
    {
        return $this->responses->make(valueObject: $this->valueObjects->make('Profile', ['id' => $id]));
    }
}
