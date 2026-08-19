<?php

namespace App\Infrastructure\Amf;

final class AmfMessage
{
    public function __construct(
        public readonly string $target,
        public readonly string $response,
        public readonly mixed $data,
    ) {}
}
