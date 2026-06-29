<?php

namespace App\Domain\Panfu\Services;

use App\Domain\Panfu\Repositories\FlashClientRepository;
use App\Domain\Panfu\Repositories\PlayerRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Session;

class FlashClientService
{
    public function __construct(
        private readonly FlashClientRepository $clients,
        private readonly PlayerRepository $players,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getPlayPage(?Authenticatable $user): array
    {
        $client = $this->clients->getClient();
        $client['flashvars'] = $this->withAuthenticatedPlayer($client['flashvars'], $user);
        $client['flashvarsQuery'] = http_build_query(
            $client['flashvars'],
            '',
            '&',
            PHP_QUERY_RFC3986,
        );

        return $client;
    }

    /**
     * @param  array<string, string>  $flashvars
     * @return array<string, string>
     */
    private function withAuthenticatedPlayer(array $flashvars, ?Authenticatable $user): array
    {
        if ($user === null) {
            return $flashvars;
        }

        $name = method_exists($user, 'getAttribute') ? $user->getAttribute('name') : null;
        $sessionKey = hash('sha256', Session::getId().'|'.$user->getAuthIdentifier());

        $flashvars['user'] = (string) ($name ?: $user->getAuthIdentifier());
        $flashvars['sessionKey'] = $sessionKey;

        $this->players->syncForFlashSession($user, $sessionKey);

        return $flashvars;
    }
}
