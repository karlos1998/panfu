<?php

return [
    'assets' => [
        'base_path' => 'vendor/panfu-me/assets',
        'favicons_path' => 'vendor/panfu-me/favicons',
    ],

    'homepage' => [
        'players_online' => 23,
    ],

    'game_client' => [
        'ruffle_script' => env('PANFU_RUFFLE_SCRIPT', '/vendor/ruffle/ruffle.js'),
        'swf_url' => env('PANFU_SWF_URL', '/vendor/openpanfu/Panfu.swf'),
        'base_url' => env('PANFU_FLASH_BASE_URL', '/vendor/openpanfu/'),
        'info_server' => env('PANFU_INFO_SERVER', '/gateway/amf/'),
        'language' => env('PANFU_LANGUAGE', 'EN'),
        'mode' => env('PANFU_MODE', 'local'),
        'server_name' => env('PANFU_SERVER_NAME', 'Local Panfu'),
    ],
];
