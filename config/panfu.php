<?php

$socketProxyUrl = (string) env('PANFU_GAME_WEBSOCKET_URL', 'ws://localhost:19596/game');
$socketProxies = json_decode((string) env('PANFU_GAME_SOCKET_PROXIES', ''), true);

if (! is_array($socketProxies) || $socketProxies === []) {
    $socketProxies = [
        ['host' => '127.0.0.1', 'port' => 9595, 'proxyUrl' => $socketProxyUrl],
        ['host' => 'localhost', 'port' => 9595, 'proxyUrl' => $socketProxyUrl],
    ];
}

return [
    'assets' => [
        'base_path' => 'vendor/panfu-me/assets',
        'favicons_path' => 'vendor/panfu-me/favicons',
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
        'max_coin_balance' => env('PANFU_MAX_COIN_BALANCE', 2_000_000_000),
        'starter_inventory' => [
            ['item_id' => 1001, 'active' => true],
            ['item_id' => 100, 'active' => true],
            ['item_id' => 103199, 'active' => false],
        ],
    ],

    'amf' => [
        'max_payload_bytes' => env('PANFU_AMF_MAX_PAYLOAD_BYTES', 1_048_576),
        'max_messages' => env('PANFU_AMF_MAX_MESSAGES', 32),
        'max_collection_entries' => env('PANFU_AMF_MAX_COLLECTION_ENTRIES', 10_000),
        'max_string_bytes' => env('PANFU_AMF_MAX_STRING_BYTES', 262_144),
        'max_depth' => env('PANFU_AMF_MAX_DEPTH', 64),
        'requests_per_minute' => env('PANFU_AMF_REQUESTS_PER_MINUTE', 240),
        'login_attempts_per_minute' => env('PANFU_AMF_LOGIN_ATTEMPTS_PER_MINUTE', 5),
        'login_attempts_per_ip' => env('PANFU_AMF_LOGIN_ATTEMPTS_PER_IP', 20),
        'registrations_per_minute' => env('PANFU_AMF_REGISTRATIONS_PER_MINUTE', 5),
        'coin_updates_per_minute' => env('PANFU_AMF_COIN_UPDATES_PER_MINUTE', 10),
        'max_headers' => env('PANFU_AMF_MAX_HEADERS', 16),
    ],

    'minigames' => [
        'max_reported_score' => env('PANFU_MAX_REPORTED_SCORE', 2_000_000_000),
    ],

    'game_server' => [
        'host' => env('PANFU_GAME_SERVER_HOST', '127.0.0.1'),
        'port' => env('PANFU_GAME_SERVER_PORT', 9595),
        'internal_url' => env('PANFU_GAME_SERVER_INTERNAL_URL', 'http://gameserver:9596'),
        'internal_secret' => env('PANFU_GAME_SERVER_INTERNAL_SECRET', 'local-development-secret-change-me'),
        'websocket_url' => $socketProxyUrl,
        'socket_proxies' => $socketProxies,
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
