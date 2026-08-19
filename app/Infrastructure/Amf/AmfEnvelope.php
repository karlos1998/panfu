<?php

namespace App\Infrastructure\Amf;

final class AmfEnvelope
{
    /** @param list<AmfMessage> $messages */
    public function __construct(
        public readonly int $encoding = 0,
        public readonly array $messages = [],
    ) {}
}
