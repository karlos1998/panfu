<?php

namespace Tests\Feature\Panfu;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_language_is_used_when_cookie_is_not_set(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
        ])->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.title', 'Entdecke die virtuelle Panda-Welt - Panfu.me')
                ->where('navigation.0.label', 'Startseite')
                ->where('navigation.2.label', 'Sprache')
                ->where('navigation.2.children.0.active', true)
                ->where('panfu.locale.current', 'de')
                ->where('panfu.locale.languageId', 'DE')
                ->etc());
    }

    public function test_locale_cookie_overrides_browser_language_for_flash_client(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withCookie('panfu_locale', 'en')
            ->withHeaders(['Accept-Language' => 'pl-PL,pl;q=0.9'])
            ->actingAs($user)
            ->get('/play');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('panfu.locale.current', 'en')
                ->where('panfu.locale.languageId', 'EN')
                ->where('client.locale', 'en')
                ->where('client.languageId', 'EN')
                ->where('client.flashvars.langId', 'EN')
                ->etc());
    }

    public function test_language_switch_sets_locale_cookie(): void
    {
        $response = $this
            ->from('/')
            ->get('/language/en');

        $response
            ->assertRedirect('/')
            ->assertCookie('panfu_locale', 'en');
    }
}
