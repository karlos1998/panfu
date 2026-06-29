<?php

return [
    'assets' => [
        'base_path' => 'vendor/panfu-me/assets',
        'favicons_path' => 'vendor/panfu-me/favicons',
    ],

    'homepage' => [
        'players_online' => 25,
    ],

    'game_client' => [
        'ruffle_script' => env('PANFU_RUFFLE_SCRIPT', '/vendor/ruffle/ruffle.js'),
        'swf_url' => env('PANFU_SWF_URL', '/vendor/openpanfu/Panfu.swf'),
        'base_url' => env('PANFU_FLASH_BASE_URL', '/vendor/openpanfu/'),
        'information_server' => env('PANFU_INFORMATION_SERVER', '/InformationServer/'),
        'legacy_information_server' => env('PANFU_LEGACY_INFORMATION_SERVER', 'http://host.docker.internal:8000/InformationServer/'),
        'sync_legacy_player' => env('PANFU_SYNC_LEGACY_PLAYER', false),
        'language_id' => env('PANFU_LANGUAGE_ID', 'EN'),
        'mode' => env('PANFU_MODE', 'dev'),
        'server_name' => env('PANFU_SERVER_NAME', 'Local Panfu'),
    ],
];
