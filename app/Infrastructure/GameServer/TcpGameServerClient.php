<?php

namespace App\Infrastructure\GameServer;

use App\Domain\Servers\GameServerClient;
use App\Domain\Servers\GameServerService;
use Illuminate\Support\Facades\Log;

final class TcpGameServerClient implements GameServerClient
{
    public function __construct(private readonly GameServerService $servers) {}

    public function send(string $command, int|string ...$parameters): bool
    {
        $server = $this->servers->first();
        if ($server === null || ! is_string($server->secret_key) || $server->secret_key === '') {
            return false;
        }

        $payload = implode(';', ['900', $server->secret_key, $command, ...$parameters]).'|';
        $host = (string) config('panfu.game_server.internal_host', 'gameserver');
        $port = (int) config('panfu.game_server.internal_port', 9595);
        $errno = 0;
        $error = '';
        $socket = @fsockopen("tcp://{$host}", $port, $errno, $error, 1.0);

        if (! is_resource($socket)) {
            Log::warning('Could not reach the game server.', compact('host', 'port', 'errno', 'error'));

            return false;
        }

        stream_set_timeout($socket, 1);
        $written = fwrite($socket, $payload);
        fclose($socket);

        return $written === strlen($payload);
    }
}
