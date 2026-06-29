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
            'serverName' => config('panfu.game_client.server_name'),
            'flashvars' => [
                'infoServer' => config('panfu.game_client.info_server'),
                'lang' => config('panfu.game_client.language'),
                'mode' => config('panfu.game_client.mode'),
            ],
        ];
    }
}
