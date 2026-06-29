<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\FlashClientRepository;

class StaticFlashClientRepository implements FlashClientRepository
{
    public function getClient(): array
    {
        return [
            'title' => 'Panfu',
            'ruffleScript' => config('panfu.game_client.ruffle_script'),
            'swfUrl' => config('panfu.game_client.swf_url'),
            'baseUrl' => config('panfu.game_client.base_url'),
            'informationServerUrl' => config('panfu.game_client.information_server'),
            'serverName' => config('panfu.game_client.server_name'),
            'socketProxyUrl' => config('panfu.game_server.socket_proxy_url'),
            'flashvars' => [
                'iServer' => config('panfu.game_client.information_server'),
                'langId' => config('panfu.game_client.language_id'),
                'mode' => config('panfu.game_client.mode'),
            ],
        ];
    }
}
