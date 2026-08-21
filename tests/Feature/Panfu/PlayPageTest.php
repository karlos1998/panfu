<?php

namespace Tests\Feature\Panfu;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlayPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_before_playing(): void
    {
        $this->get('/play')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_local_flash_client(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withHeaders(['Accept-Language' => 'pl-PL,pl;q=0.9'])
            ->actingAs($user)
            ->get('/play');

        $response
            ->assertOk()
            ->assertDontSee('Try Ruffle')
            ->assertDontSee('Download')
            ->assertDontSee("Your browser doesn't support Flash Player")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panfu/Play')
                ->where('client.ruffleScript', '/vendor/ruffle/ruffle.js')
                ->where('client.swfUrl', '/vendor/openpanfu/Panfu.swf')
                ->where('client.baseUrl', '/vendor/openpanfu/')
                ->where('client.informationServerUrl', '/InformationServer/')
                ->where('client.socketProxyUrl', 'ws://localhost:19596/game')
                ->where('client.socketProxies.0.host', '127.0.0.1')
                ->where('client.socketProxies.0.port', 9595)
                ->where('client.socketProxies.0.proxyUrl', 'ws://localhost:19596/game')
                ->where('client.flashvars.iServer', '/InformationServer/')
                ->where('client.locale', 'pl')
                ->where('client.languageId', 'PL')
                ->where('client.flashvars.langId', 'PL')
                ->where('client.flashvars.mode', 'dev')
                ->where('client.flashvars.user', $user->name)
                ->has('client.flashvars.sessionKey')
                ->etc());
    }

    public function test_dashboard_route_redirects_to_play(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('play'));
    }
}
