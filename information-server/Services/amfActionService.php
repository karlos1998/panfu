<?php
/**
 * This file is part of openPanfu, a project that imitates the Flex remoting
 * and gameservers of Panfu.
 *
 * @category AMF Service
 * @package com.pandaland.api.service
 * @author Altro50 <altro50@msn.com>
 */

require_once 'Vo/AmfResponse.php';
require_once 'Vo/UserActionDailyVO.php';

class amfActionService 
{

    public function getLastDoneActionToday($id, $action, $time)
    {
        if(!Panfu::isLoggedIn())
            return;
        //TODO: Implement.
        $response = new AmfResponse();
        $response->valueObject = new UserActionDailyVO();
        $response->valueObject->playerId = $_SESSION['id'];
        $response->message = $action;
        return $response;
    }

    public function performAction($playerId, $action)
    {
        if(!Panfu::isLoggedIn())
            return;

        $response = new AmfResponse();
        Console::log("Player Id " . $playerId . " performed " . $action);

        if($playerId == $_SESSION['id'] && $action == "played10") {
            if(!isset($_SESSION['lastPlayed10'])) {
                $_SESSION['lastPlayed10'] = 0;
            }

            $secondsSinceLastPlayed10 = time() - $_SESSION['lastPlayed10'];
            Console::log($_SESSION['lastPlayed10'], time());
    
            if($secondsSinceLastPlayed10 < Panfu::played10CooldownSeconds()) {
                $response->statusCode = 1;
                $response->message = "lastplayed10 denied " . $secondsSinceLastPlayed10 . " seconds since last request.";
                return $response;
            }
            $response->message = "lastplayed10 accepted " . $secondsSinceLastPlayed10 . " seconds since last request.";
            $_SESSION['lastPlayed10'] = time();
            $response->valueObject = Panfu::played10();
        }
        return $response;
    }
}
