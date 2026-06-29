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
                ->where('meta.title', 'Discover the virtual world of pandas - Panfu.me')
                ->where('hero.playersOnline', 25)
                ->has('hero.features', 4)
                ->where('navigation.0.label', 'Home')
                ->where('navigation.2.children.1.label', 'English')
                ->where('news.title', 'Panfu Year in Review 2025')
                ->where('about.title', 'What is Panfu.me?')
                ->where('footer.legalLinks.2.label', 'Terms of Service')
                ->where('assets.logo', asset('vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg'))
                ->etc());
    }
}
