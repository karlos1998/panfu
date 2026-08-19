<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfAuthenticationThrottle;
use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Pets\PetService;
use App\Domain\Player\PlayerService;
use App\Domain\Servers\GameServerClient;
use App\Domain\Servers\GameServerService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;

final class ConnectionAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
        private readonly PlayerService $players,
        private readonly PetService $pets,
        private readonly GameServerService $servers,
        private readonly GameServerClient $gameServer,
        private readonly AmfAuthenticationThrottle $authenticationThrottle,
    ) {}

    public function doLogin(mixed $login): TypedObject
    {
        if (is_string($login)) {
            return $this->doLoginSession($login);
        }
        if (! $login instanceof TypedObject || $login->type !== 'com.pandaland.mvc.model.vo.LoginVO') {
            return $this->responses->make(1);
        }

        $name = (string) $login->get('playerName', '');
        if ($this->authenticationThrottle->blocked($name)) {
            return $this->responses->make(1, 'Too many login attempts.');
        }

        $player = $this->players->authenticate($name, (string) $login->get('pw', ''));
        if ($player === null) {
            $this->authenticationThrottle->failed($name);

            return $this->responses->make(1);
        }
        $this->authenticationThrottle->succeeded($name);

        return $this->loggedIn($player);
    }

    public function doLoginSession(string|int $ticket): TypedObject
    {
        $ticket = (string) $ticket;
        if ($this->authenticationThrottle->blocked($ticket)) {
            return $this->responses->make(1, 'Too many login attempts.');
        }

        $player = $this->players->authenticateTicket($ticket);
        if ($player === null) {
            $this->authenticationThrottle->failed($ticket);

            return $this->responses->make(1);
        }
        $this->authenticationThrottle->succeeded($ticket);

        $this->gameServer->send('testConnection');

        return $this->loggedIn($player);
    }

    public function doRegister(mixed $data): TypedObject
    {
        if ($this->authenticationThrottle->registrationBlocked()) {
            return $this->responses->make(1, 'Too many registration attempts.');
        }
        $this->authenticationThrottle->registrationAttempted();

        return $data instanceof TypedObject && $this->players->register($data) !== null
            ? $this->responses->make()
            : $this->responses->make(1);
    }

    public function setEmailAddress(mixed ...$arguments): TypedObject
    {
        return $this->responses->make();
    }

    public function checkUserName(string $name): TypedObject
    {
        return $this->players->nameAvailable($name)
            ? $this->responses->make()
            : $this->responses->make(1, valueObject: $this->valueObjects->make('Feedback'));
    }

    public function checkEmailAddress(string $email): TypedObject
    {
        return $this->responses->make();
    }

    public function ping(): TypedObject
    {
        return $this->responses->make($this->session->player() === null ? 1 : 0);
    }

    private function loggedIn(User $player): TypedObject
    {
        $this->session->login($player);
        $this->players->ensureStarterInventory($player);
        $player->refresh();

        $result = $this->valueObjects->make('LoginResult', [
            'partnerTracking' => $this->valueObjects->make('PartnerTracking'),
            'membershipStatus' => 0,
            'email' => (string) $player->email,
            'ticketId' => $this->players->issueGameTicket($player),
            'gameServers' => $this->servers->available(),
            'showTour' => false,
            'playerInfo' => $this->players->info($player->refresh()),
            'hungryPokoPets' => $this->pets->withoutHealth($player),
        ]);

        return $this->responses->make(valueObject: $result);
    }
}
