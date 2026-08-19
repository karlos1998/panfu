<?php

return [
    'assets' => [
        'base_path' => 'vendor/panfu-me/assets',
        'favicons_path' => 'vendor/panfu-me/favicons',
    ],

    'homepage' => [
        'players_online' => 28,
    ],

    'localization' => [
        'cookie' => env('PANFU_LOCALE_COOKIE', 'panfu_locale'),
        'fallback' => env('PANFU_FALLBACK_LOCALE', 'pl'),
        'supported' => [
            'de' => [
                'id' => 'DE',
                'label' => 'Deutsch',
            ],
            'en' => [
                'id' => 'EN',
                'label' => 'English',
            ],
            'pl' => [
                'id' => 'PL',
                'label' => 'Polski',
            ],
        ],
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

    'player' => [
        'starter_inventory' => [
            ['item_id' => 1001, 'active' => true],
            ['item_id' => 100, 'active' => true],
            ['item_id' => 103199, 'active' => false],
        ],
    ],

    'game_server' => [
        'host' => env('PANFU_GAME_SERVER_HOST', '127.0.0.1'),
        'port' => env('PANFU_GAME_SERVER_PORT', 9595),
        'internal_host' => env('PANFU_GAME_SERVER_INTERNAL_HOST', 'gameserver'),
        'internal_port' => env('PANFU_GAME_SERVER_INTERNAL_PORT', 9595),
        'secret_key' => env('PANFU_GAME_SERVER_SECRET_KEY', 'local-development-secret'),
        'socket_proxy_url' => env('PANFU_GAME_SOCKET_PROXY_URL', 'ws://localhost:19596'),
    ],

    'game_client' => [
        'ruffle_script' => env('PANFU_RUFFLE_SCRIPT', '/vendor/ruffle/ruffle.js'),
        'swf_url' => env('PANFU_SWF_URL', '/vendor/openpanfu/Panfu.swf'),
        'base_url' => env('PANFU_FLASH_BASE_URL', '/vendor/openpanfu/'),
        'information_server' => env('PANFU_INFORMATION_SERVER', '/InformationServer/'),
        'language_id' => env('PANFU_LANGUAGE_ID', null),
        'mode' => env('PANFU_MODE', 'dev'),
        'server_name' => env('PANFU_SERVER_NAME', 'Local Panfu'),
    ],
];
