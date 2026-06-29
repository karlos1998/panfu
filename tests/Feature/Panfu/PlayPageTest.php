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

        $response = $this->actingAs($user)->get('/play');

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
                ->where('client.flashvars.iServer', '/InformationServer/')
                ->where('client.flashvars.langId', 'EN')
                ->where('client.flashvars.mode', 'dev')
                ->where('client.flashvars.user', $user->name)
                ->has('client.flashvars.sessionKey')
                ->etc());
    }

    public function test_dashboard_route_keeps_legacy_auth_redirects_working(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('play'));
    }
}
