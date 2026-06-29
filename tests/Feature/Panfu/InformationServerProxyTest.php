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
            'panfu.game_client.legacy_information_server' => 'http://legacy.test/InformationServer/',
        ]);

        Http::fake([
            'legacy.test/InformationServer/gateway/amf' => Http::response('amf-response', 200, [
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

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'http://legacy.test/InformationServer/gateway/amf'
            && $request->body() === 'amf-payload'
            && $request->method() === 'POST');
    }
}
