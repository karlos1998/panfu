<?php

/**
 * Pokopet AMF service used by the Flash client.
 */

require_once 'Vo/AmfResponse.php';
require_once 'Vo/GameServerVO.php';

class amfPetService
{
    public function buyPet($type, $name)
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            $response->message = 'Not logged in.';
            return $response;
        }

        $voucherReserved = !empty($_SESSION['pokopet_voucher_reserved_at'])
            && (int)$_SESSION['pokopet_voucher_reserved_at'] >= time() - 300;

        $result = Panfu::buyPokoPet($_SESSION['id'], $type, $name, $voucherReserved);
        if((int)$type === 5) {
            unset($_SESSION['pokopet_voucher_reserved_at']);
        }

        $response->statusCode = $result['statusCode'];
        $response->message = $result['message'];
        $response->valueObject = $result['pet'];
        return $response;
    }

    public function switchPet($petId)
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        if(!Panfu::switchPokoPet($_SESSION['id'], $petId)) {
            $response->statusCode = 3;
            $response->message = 'Pokopet not found.';
        }
        return $response;
    }

    public function updatePetState($petId, $state)
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        $pet = Panfu::updatePokoPetState($_SESSION['id'], $petId, $state);
        if(!$pet) {
            $response->statusCode = 3;
            $response->message = 'Pokopet not found or state is invalid.';
            return $response;
        }

        $response->valueObject = $pet;
        return $response;
    }

    public function removePet($petId)
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        if(!Panfu::removePokoPet($_SESSION['id'], $petId)) {
            $response->statusCode = 3;
            $response->message = 'Pokopet not found.';
        }
        return $response;
    }

    public function feed($petId)
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        $health = Panfu::feedPokoPet($_SESSION['id'], $petId);
        if($health === null) {
            $response->statusCode = 3;
            $response->message = 'Pokopet not found.';
            return $response;
        }

        $response->valueObject = $health;
        return $response;
    }

    public function increaseHealth()
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        $pet = Panfu::increaseSelectedPokoPetHealth($_SESSION['id']);
        if(!$pet) {
            $response->statusCode = 3;
            $response->message = 'No selected Pokopet can recover health.';
            return $response;
        }

        $response->valueObject = $pet;
        return $response;
    }

    public function getGameServer()
    {
        $response = new AmfResponse();
        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        $servers = Panfu::getGameServers();
        if(!$servers) {
            $response->statusCode = 3;
            $response->message = 'No game server is available.';
            return $response;
        }

        $currentUser = Panfu::getUserDataById($_SESSION['id']);
        $currentServerId = (int)($currentUser['current_gameserver'] ?? 0);
        $response->valueObject = $servers[0];
        foreach($servers as $server) {
            if((int)$server->id === $currentServerId) {
                $response->valueObject = $server;
                break;
            }
        }

        return $response;
    }
}
