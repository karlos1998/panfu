<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\FlashClientRepository;

class StaticFlashClientRepository implements FlashClientRepository
{
    public function getClient(): array
    {
        return [
            'title' => 'Panfu',
            'ruffleScript' => $this->versionedAssetUrl((string) config('panfu.game_client.ruffle_script')),
            'swfUrl' => $this->versionedAssetUrl((string) config('panfu.game_client.swf_url')),
            'baseUrl' => config('panfu.game_client.base_url'),
            'informationServerUrl' => config('panfu.game_client.information_server'),
            'serverName' => config('panfu.game_client.server_name'),
            'socketProxyUrl' => config('panfu.game_server.websocket_url'),
            'socketProxies' => config('panfu.game_server.socket_proxies'),
            'flashvars' => [
                'iServer' => config('panfu.game_client.information_server'),
                'langId' => config('panfu.game_client.language_id') ?: 'EN',
                'mode' => config('panfu.game_client.mode'),
            ],
        ];
    }

    private function versionedAssetUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($path) || $path === '' || $host !== null || ! str_starts_with($path, '/')) {
            return $url;
        }

        $file = public_path(ltrim($path, '/'));

        if (! is_file($file)) {
            return $url;
        }

        $hash = hash_file('sha256', $file);

        if ($hash === false) {
            return $url;
        }

        [$assetUrl, $fragment] = array_pad(explode('#', $url, 2), 2, null);
        $separator = str_contains($assetUrl, '?') ? '&' : '?';

        return $assetUrl.$separator.'v='.substr($hash, 0, 12).($fragment === null ? '' : '#'.$fragment);
    }
}
