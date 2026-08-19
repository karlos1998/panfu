<?php

namespace App\Application\Amf;

use App\Infrastructure\Amf\TypedObject;

final class AmfResponseFactory
{
    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    public function make(int $statusCode = 0, string $message = '', mixed $valueObject = null): TypedObject
    {
        return $this->valueObjects->make('AmfResponse', compact('statusCode', 'message', 'valueObject'));
    }
}
