<?php

namespace Tests\Feature\Panfu;

use App\Models\GameServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_panfu_landing_page(): void
    {
        GameServer::query()->create(['name' => 'Pandama', 'player_count' => 12]);
        GameServer::query()->create(['name' => 'Bollywood', 'player_count' => 7]);

        $response = $this
            ->withHeaders(['Accept-Language' => 'pl-PL,pl;q=0.9'])
            ->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panfu/Home')
                ->where('meta.title', 'Odkryj wirtualny świat pand - Panfu.me')
                ->where('hero.playersOnline', 19)
                ->has('hero.features', 4)
                ->where('navigation.0.label', 'Strona główna')
                ->where('navigation.2.children.0.href', '/language/de')
                ->where('navigation.2.children.2.label', 'Polski')
                ->where('navigation.2.children.2.active', true)
                ->where('panfu.locale.current', 'pl')
                ->where('panfu.locale.languageId', 'PL')
                ->where('news.title', 'Podsumowanie roku Panfu 2025')
                ->where('about.title', 'Czym jest Panfu.me?')
                ->where('about.button.href', '/team')
                ->where('footer.links.1.href', '/team')
                ->where('footer.legalLinks.2.label', 'Terms of Service')
                ->where('assets.logo', asset('vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg'))
                ->where('assets.heroVideoSafari', asset('vendor/panfu-me/assets/home-C5LnHByY-hevc.mov'))
                ->etc());
    }
}
