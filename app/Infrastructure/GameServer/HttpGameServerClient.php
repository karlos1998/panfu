<?php

namespace App\Infrastructure\GameServer;

use App\Domain\Servers\GameServerClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HttpGameServerClient implements GameServerClient
{
    public function send(string $command, int|string ...$parameters): bool
    {
        try {
            return match ($command) {
                'testConnection' => $this->request('GET', '/internal/v1/health/connection', ''),
                'kickUser' => isset($parameters[0])
                    && $this->request('POST', '/internal/v1/players/'.(int) $parameters[0].'/kick', '{}'),
                'updateBuddyStatus' => count($parameters) >= 3
                    && $this->request(
                        'POST',
                        '/internal/v1/players/'.(int) $parameters[0].'/buddy-status',
                        $this->json([
                            'buddyId' => (int) $parameters[1],
                            'status' => (int) $parameters[2],
                        ]),
                    ),
                default => false,
            };
        } catch (Throwable $exception) {
            Log::warning('Could not reach the game server internal API.', [
                'command' => $command,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function request(string $method, string $path, string $body): bool
    {
        $baseUrl = rtrim((string) config('panfu.game_server.internal_url'), '/');
        $secret = (string) config('panfu.game_server.internal_secret');
        if ($baseUrl === '' || $secret === '') {
            return false;
        }

        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = implode("\n", [$method, $path, $timestamp, $nonce, hash('sha256', $body)]);
        $signature = hash_hmac('sha256', $canonical, $secret);
        $request = Http::connectTimeout(0.5)
            ->timeout(1.5)
            ->withHeaders([
                'X-Panfu-Timestamp' => $timestamp,
                'X-Panfu-Nonce' => $nonce,
                'X-Panfu-Signature' => $signature,
            ]);

        $response = $method === 'GET'
            ? $request->get($baseUrl.$path)
            : $request->withBody($body, 'application/json')->post($baseUrl.$path);

        return $response->successful();
    }

    /** @param array<string, int> $payload */
    private function json(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
