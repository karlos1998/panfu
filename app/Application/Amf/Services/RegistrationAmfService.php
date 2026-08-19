<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfAuthenticationThrottle;
use App\Application\Amf\AmfResponseFactory;
use App\Domain\Player\PlayerService;
use App\Infrastructure\Amf\TypedObject;

final class RegistrationAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly PlayerService $players,
        private readonly AmfAuthenticationThrottle $authenticationThrottle,
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
        $base = mb_substr((string) preg_replace('/[^A-Za-z0-9_]/', '', $name), 0, 7);
        if ($base === '' || ! $this->players->nameAcceptable($base.'7000')) {
            $base = 'Panda';
        }

        $suggestion = '';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = mb_substr($base.random_int(7000, 19000), 0, 12);
            if ($this->players->nameAvailable($candidate)) {
                $suggestion = $candidate;
                break;
            }
        }

        if ($suggestion === '') {
            return $this->responses->make(1, 'Could not generate a username suggestion.', []);
        }

        return $this->responses->make(valueObject: [$suggestion]);
    }

    public function checkEmailAddress(string $email): TypedObject
    {
        return $this->responses->make(valueObject: true);
    }

    public function register(mixed $data): int|TypedObject
    {
        if ($this->authenticationThrottle->registrationBlocked()) {
            return $this->responses->make(1, 'Too many registration attempts.');
        }
        $this->authenticationThrottle->registrationAttempted();

        return $data instanceof TypedObject && $this->players->register($data) !== null
            ? 0
            : $this->responses->make(1);
    }
}
