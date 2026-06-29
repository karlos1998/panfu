<?php

namespace Tests\Feature\Panfu;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InformationServerProxyTest extends TestCase
{
    public function test_information_server_proxy_forwards_amf_payloads(): void
    {
        config([
            'panfu.game_client.information_server_upstream' => 'http://information-server.test/',
        ]);

        Http::fake([
            'information-server.test/gateway/amf' => Http::response('amf-response', 200, [
                'Content-Type' => 'application/x-amf',
            ]),
        ]);

        $response = $this->call(
            method: 'POST',
            uri: '/InformationServer/gateway/amf',
            server: ['CONTENT_TYPE' => 'application/x-amf'],
            content: 'amf-payload',
        );

        $response
            ->assertOk()
            ->assertContent('amf-response');

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'http://information-server.test/gateway/amf'
            && $request->body() === 'amf-payload'
            && $request->method() === 'POST');
    }

    public function test_information_server_proxy_preserves_session_cookies(): void
    {
        config([
            'panfu.game_client.information_server_upstream' => 'http://information-server.test/',
        ]);

        $requests = [];

        Http::fake(function (ClientRequest $request) use (&$requests) {
            $requests[] = $request;

            return Http::response('amf-response', 200, array_filter([
                'Content-Type' => 'application/x-amf',
                'Set-Cookie' => count($requests) === 1 ? 'PHPSESSID=panfu-session; path=/' : null,
            ]));
        });

        $this->withSession([]);

        $this->call(
            method: 'POST',
            uri: '/InformationServer/gateway/amf',
            server: ['CONTENT_TYPE' => 'application/x-amf'],
            content: 'login-payload',
        )->assertOk();

        $this->call(
            method: 'POST',
            uri: '/InformationServer/gateway/amf',
            server: ['CONTENT_TYPE' => 'application/x-amf'],
            content: 'states-payload',
        )->assertOk();

        $this->assertCount(2, $requests);
        $this->assertStringContainsString(
            'PHPSESSID=panfu-session',
            implode('; ', $requests[1]->header('Cookie')),
        );
    }
}
