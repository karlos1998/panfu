<?php

/**
 * This file is part of openPanfu, a project that imitates the Flex remoting
 * and gameservers of Panfu.
 *
 * @category Utility
 * @author Altro50 <altro50@msn.com>
 */

 class GSCommunicator
 {
    public static function checkConnection()
    {
        GSCommunicator::communicate("testConnection");
    }

    public static function communicate()
    {
        $servers = Panfu::getGameServers();
        if(!isset($servers[0])) {
            Console::log("Unable to communicate with the gameserver: no servers are configured.");
            return;
        }

        $key = Panfu::getGameServerKey($servers[0]->id);
        $command = "900;$key";
        foreach (func_get_args() as $param) {
            $command .= ";$param";
        }
        $command .= "|";

        $host = getenv('PANFU_GAME_SERVER_INTERNAL_HOST') ?: $servers[0]->url;
        $port = getenv('PANFU_GAME_SERVER_INTERNAL_PORT') ?: $servers[0]->port;

        $connection = fsockopen("tcp://" . $host . "", (int) $port, $error, $errorStr);

        // Connection failed somehow.
        if(!$connection) {
            Console::log("An error occured while communicating message: $command to the gameserver.; $error: $errorStr");
            return;
        }

        fwrite($connection, $command);
        fclose($connection);
    }
 }
