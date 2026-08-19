<?php

namespace Tests\Feature\Amf;

use App\Application\Amf\PlayerSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_player_and_namespaced_transient_values(): void
    {
        $this->withSession([]);
        $player = User::factory()->create();
        $session = $this->app->make(PlayerSession::class);

        $session->login($player);
        $session->put('voucher', 123);

        $this->assertTrue($player->is($session->player()));
        $this->assertSame(123, $session->get('voucher'));
        $session->forget('voucher');
        $this->assertSame('fallback', $session->get('voucher', 'fallback'));

        $session->logout();
        $this->assertNull($session->player());
    }

    public function test_it_clears_a_stale_player_id_when_the_account_no_longer_exists(): void
    {
        $this->withSession([]);
        $player = User::factory()->create();
        $session = $this->app->make(PlayerSession::class);
        $session->login($player);
        $player->delete();

        $this->assertNull($session->player());
        $this->assertFalse(session()->has('amf.player_id'));
    }

    public function test_non_numeric_player_ids_are_never_looked_up(): void
    {
        $this->withSession(['amf.player_id' => 'not-an-id']);

        $this->assertNull($this->app->make(PlayerSession::class)->player());
    }
}
