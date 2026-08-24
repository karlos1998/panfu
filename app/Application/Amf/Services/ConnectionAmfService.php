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
use App\Models\PinboardMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

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

    public function doLogout(): TypedObject
    {
        $player = $this->session->player();
        if ($player !== null) {
            $player->forceFill(['current_gameserver' => 0])->save();
        }
        $this->session->logout();

        return $this->responses->make();
    }

    public function setEmailAddress(int $playerId, string $email, bool $confirmed = true): TypedObject
    {
        $player = $this->session->player();
        if ($player === null || (int) $player->getKey() !== $playerId || ! $this->validEmail($email, $playerId)) {
            return $this->responses->make(1);
        }

        $player->forceFill([
            'email' => strtolower($email),
            'email_verified_at' => $confirmed ? now() : null,
        ])->save();

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
        return $this->validEmail($email)
            ? $this->responses->make()
            : $this->responses->make(1, valueObject: $this->valueObjects->make('Feedback'));
    }

    public function setBirthday(TypedObject $birthday): TypedObject
    {
        $player = $this->session->player();
        $timestamp = $birthday->get('date');
        if ($player === null || ! is_numeric($timestamp)) {
            return $this->responses->make(1);
        }

        $date = Carbon::createFromTimestampMs((int) $timestamp)->startOfDay();
        if ($date->isFuture() || $date->age < 3 || $date->age > 120) {
            return $this->responses->make(1);
        }

        $player->forceFill(['birthday' => $date->toDateString()])->save();

        return $this->responses->make(valueObject: $this->valueObjects->make('Date', [
            'date' => $date->getTimestampMs(),
        ]));
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

        $messages = PinboardMessage::query()->where('receiver_id', $player->getKey())->where('deleted', false);
        $result = $this->valueObjects->make('LoginResult', [
            'partnerTracking' => $this->valueObjects->make('PartnerTracking'),
            'membershipStatus' => 0,
            'email' => (string) $player->email,
            'ticketId' => $this->players->issueGameTicket($player),
            'gameServers' => $this->servers->available(),
            'showTour' => false,
            'playerInfo' => $this->players->info($player->refresh()),
            'hungryPokoPets' => $this->pets->withoutHealth($player),
            'unreadMessagesCount' => (clone $messages)->where('read', false)->count(),
            'undeletedMessagesCount' => $messages->count(),
        ]);

        return $this->responses->make(valueObject: $result);
    }

    private function validEmail(string $email, ?int $ignorePlayerId = null): bool
    {
        $validator = Validator::make(['email' => strtolower($email)], [
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);
        if ($validator->fails()) {
            return false;
        }

        return ! User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->when($ignorePlayerId !== null, fn ($query) => $query->whereKeyNot($ignorePlayerId))
            ->exists();
    }
}
