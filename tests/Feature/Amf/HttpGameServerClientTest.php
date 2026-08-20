<?php

namespace Tests\Feature\Amf;

use App\Infrastructure\GameServer\HttpGameServerClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpGameServerClientTest extends TestCase
{
    public function test_it_refuses_to_send_without_internal_url_and_secret(): void
    {
        config([
            'panfu.game_server.internal_url' => '',
            'panfu.game_server.internal_secret' => '',
        ]);

        Http::fake();

        $this->assertFalse($this->app->make(HttpGameServerClient::class)->send('testConnection'));
        Http::assertNothingSent();
    }

    public function test_it_signs_the_exact_buddy_status_request(): void
    {
        config([
            'panfu.game_server.internal_url' => 'http://gameserver:9596',
            'panfu.game_server.internal_secret' => 'test-secret',
        ]);
        Http::fake(['*' => Http::response(status: 204)]);

        $this->assertTrue($this->app->make(HttpGameServerClient::class)->send(
            'updateBuddyStatus',
            7,
            9,
            1,
        ));

        Http::assertSent(function (Request $request): bool {
            $timestamp = $request->header('X-Panfu-Timestamp')[0] ?? '';
            $nonce = $request->header('X-Panfu-Nonce')[0] ?? '';
            $signature = $request->header('X-Panfu-Signature')[0] ?? '';
            $body = '{"buddyId":9,"status":1}';
            $path = '/internal/v1/players/7/buddy-status';
            $canonical = implode("\n", ['POST', $path, $timestamp, $nonce, hash('sha256', $body)]);

            return $request->url() === 'http://gameserver:9596'.$path
                && $request->method() === 'POST'
                && $request->body() === $body
                && strlen($nonce) === 32
                && hash_equals(hash_hmac('sha256', $canonical, 'test-secret'), $signature);
        });
    }

    public function test_it_maps_health_and_kick_commands_and_rejects_unknown_commands(): void
    {
        config([
            'panfu.game_server.internal_url' => 'http://gameserver:9596',
            'panfu.game_server.internal_secret' => 'test-secret',
        ]);
        Http::fake(['*' => Http::response(status: 204)]);
        $client = $this->app->make(HttpGameServerClient::class);

        $this->assertTrue($client->send('testConnection'));
        $this->assertTrue($client->send('kickUser', 7));
        $this->assertFalse($client->send('unsupportedCommand'));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://gameserver:9596/internal/v1/health/connection');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://gameserver:9596/internal/v1/players/7/kick'
            && $request->body() === '{}');
    }
}
