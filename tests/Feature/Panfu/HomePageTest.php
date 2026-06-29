<?php

namespace Tests\Feature\Panfu;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_panfu_landing_page(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panfu/Home')
                ->where('meta.title', 'Odkryj wirtualny świat pand - Panfu.me')
                ->where('hero.playersOnline', 28)
                ->has('hero.features', 4)
                ->where('navigation.0.label', 'Strona główna')
                ->where('navigation.2.children.2.label', 'Polski')
                ->where('news.title', 'Podsumowanie roku Panfu 2025')
                ->where('about.title', 'Czym jest Panfu.me?')
                ->where('footer.legalLinks.2.label', 'Terms of Service')
                ->where('assets.logo', asset('vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg'))
                ->etc());
    }
}
