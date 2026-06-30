<?php
/**
 * This file is part of openPanfu, a project that imitates the Flex remoting
 * and gameservers of Panfu.
 *
 * @category AMF Service
 * @package com.pandaland.api.service
 */

require_once 'Vo/AmfResponse.php';
require_once 'Vo/ListVO.php';
require_once 'Vo/SocialHighscoreVO.php';

class amfSocialHighscoreService
{
    public function getSocialHighscore($playerId, $otherPlayerId = -1)
    {
        $response = new AmfResponse();
        $response->valueObject = new ListVO();
        $response->valueObject->list = [];

        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        $currentPlayerId = (int) $_SESSION['id'];
        $playerId = (int) $playerId;
        $otherPlayerId = (int) $otherPlayerId;

        if($playerId <= 0 || !Panfu::getUserDataById($playerId)) {
            $playerId = $currentPlayerId;
        }

        if($otherPlayerId <= 0 || $otherPlayerId === $playerId || !Panfu::getUserDataById($otherPlayerId)) {
            $otherPlayerId = -1;
        }

        $playerScores = Panfu::getGameHighScoresForUser($playerId);
        $otherPlayerScores = $otherPlayerId > 0 ? Panfu::getGameHighScoresForUser($otherPlayerId) : [];

        foreach(Panfu::getSocialHighscoreGameIds() as $gameId) {
            $response->valueObject->list[] = $this->makeHighscoreEntry(
                $gameId,
                $playerId,
                $otherPlayerId,
                $playerScores,
                $otherPlayerScores
            );
        }

        return $response;
    }

    private function makeHighscoreEntry($gameId, $playerId, $otherPlayerId, $playerScores, $otherPlayerScores)
    {
        $entry = new SocialHighscoreVO();
        $entry->gameID = (int) $gameId;
        $entry->playerID = (int) $playerId;
        $entry->otherPlayerID = (int) $otherPlayerId;
        $entry->playerScore = $this->scoreFor($playerScores, $gameId);
        $entry->otherPlayerScore = $otherPlayerId > 0 ? $this->scoreFor($otherPlayerScores, $gameId) : 0;

        return $entry;
    }

    private function scoreFor($scores, $gameId)
    {
        $gameId = (int) $gameId;

        return isset($scores[$gameId]) ? (int) $scores[$gameId] : 0;
    }
}
