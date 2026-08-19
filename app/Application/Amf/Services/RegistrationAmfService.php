<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Domain\Player\PlayerService;
use App\Infrastructure\Amf\TypedObject;

final class RegistrationAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerService $players,
    ) {}

    public function checkUserName(string $name): TypedObject
    {
        $value = $this->players->nameAcceptable($name)
            ? $this->players->nameAvailable($name)
            : 'BLACKLISTED';

        return $this->responses->make(valueObject: $value);
    }

    public function loadUsernameSuggestions(string $name, mixed ...$arguments): TypedObject
    {
        do {
            $suggestion = mb_substr($name.random_int(7000, 19000), 0, 12);
        } while (! $this->players->nameAvailable($suggestion));

        return $this->responses->make(valueObject: [$suggestion]);
    }

    public function checkEmailAddress(string $email): TypedObject
    {
        return $this->responses->make(valueObject: true);
    }

    public function register(mixed $data): int|TypedObject
    {
        return $data instanceof TypedObject && $this->players->register($data) !== null
            ? 0
            : $this->responses->make(1);
    }
}
