<?php

return [
    'assets' => [
        'base_path' => 'vendor/panfu-me/assets',
        'favicons_path' => 'vendor/panfu-me/favicons',
    ],

    'homepage' => [
        'players_online' => 28,
    ],

    'shop' => [
        'catalogue_path' => env('PANFU_SHOP_CATALOGUE_PATH', resource_path('data/panfu/shop.json')),
        'default_coins' => env('PANFU_DEFAULT_COINS', 1000),
    ],

    'leveling' => [
        'max_level' => env('PANFU_LEVEL_MAX', 60),
        'base_minutes' => env('PANFU_LEVEL_BASE_MINUTES', 10),
        'growth_rate' => env('PANFU_LEVEL_GROWTH_RATE', 0.10),
        'tick_seconds' => env('PANFU_LEVEL_TICK_SECONDS', 600),
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
