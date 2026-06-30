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
require_once 'Vo/GameHighScoresVO.php';
require_once 'Vo/HighScoreEntryVO.php';

class amfGameService
{
    public function setHighScore($gameId, $score)
    {
        $response = new AmfResponse();

        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        Panfu::recordGameHighScore($_SESSION['id'], $gameId, $score);

        return $response;
    }

    public function getHighScoreLists($gameId)
    {
        $response = new AmfResponse();
        $response->valueObject = new GameHighScoresVO();
        $response->valueObject->id = (int) $gameId;
        $response->valueObject->dailyHighscores = $this->highScoreEntries($gameId, '-1 day');
        $response->valueObject->weeklyHighscores = $this->highScoreEntries($gameId, '-1 week');
        $response->valueObject->overAllHighscores = $this->highScoreEntries($gameId);

        return $response;
    }

    public function finishMinigame($gameId, $score)
    {
        $response = new AmfResponse();

        if(!Panfu::isLoggedIn()) {
            $response->statusCode = 1;
            return $response;
        }

        Panfu::recordGameHighScore($_SESSION['id'], $gameId, $score);

        return $response;
    }

    private function highScoreEntries($gameId, $since = null)
    {
        $rows = Panfu::getGameHighScoreRows($gameId, $since);
        $entries = [];
        $ranking = 1;

        foreach($rows as $row) {
            $entry = new HighScoreEntryVO();
            $entry->ranking = $ranking;
            $entry->playerID = (string) $row['user_id'];
            $entry->playerName = (string) $row['name'];
            $entry->score = (int) $row['score'];

            $entries[] = $entry;
            $ranking++;
        }

        return $entries;
    }
}
