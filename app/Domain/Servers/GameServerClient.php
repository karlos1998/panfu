<?php

namespace App\Domain\Servers;

interface GameServerClient
{
    public function send(string $command, int|string ...$parameters): bool;
}
