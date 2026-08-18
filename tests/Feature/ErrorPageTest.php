<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        foreach ([401, 402, 403, 419, 429, 500, 503] as $status) {
            Route::middleware('web')->get(
                "/__error-page-test/{$status}",
                fn () => abort($status),
            );
        }

        Route::middleware('web')->get(
            '/__error-page-test/unexpected-exception',
            fn () => throw new RuntimeException('Sensitive exception details'),
        );
    }

    public function test_missing_route_uses_the_static_panfu_error_page(): void
    {
        $this->get('/this-panfu-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Return to home')
            ->assertSee('data-error-status="404"', false)
            ->assertSee('/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg', false)
            ->assertSee('aria-label="Facebook"', false)
            ->assertSee('aria-label="Discord"', false)
            ->assertDontSee('data-page=', false)
            ->assertDontSee('Moje konto')
            ->assertDontSee('Admin');
    }

    #[DataProvider('defaultErrorPages')]
    public function test_laravel_default_error_statuses_use_the_panfu_template(
        int $status,
        string $heading,
    ): void {
        $this->get("/__error-page-test/{$status}")
            ->assertStatus($status)
            ->assertSee($heading)
            ->assertSee("data-error-status=\"{$status}\"", false)
            ->assertDontSee('data-page=', false);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function defaultErrorPages(): array
    {
        return [
            'unauthorized' => [401, 'Sign in required'],
            'payment required' => [402, 'Payment required'],
            'forbidden' => [403, 'Access denied'],
            'page expired' => [419, 'Page expired'],
            'too many requests' => [429, 'Too many requests'],
            'server error' => [500, 'Something went wrong'],
            'service unavailable' => [503, 'We will be right back'],
        ];
    }

    #[DataProvider('fallbackErrorPages')]
    public function test_other_http_errors_use_the_family_fallback(
        int $status,
        string $heading,
    ): void {
        Route::middleware('web')->get(
            "/__error-page-test/fallback/{$status}",
            fn () => abort($status),
        );

        $this->get("/__error-page-test/fallback/{$status}")
            ->assertStatus($status)
            ->assertSee($heading)
            ->assertSee("data-error-status=\"{$status}\"", false);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function fallbackErrorPages(): array
    {
        return [
            'other client error' => [418, 'Request could not be completed'],
            'other server error' => [502, 'Something went wrong'],
        ];
    }

    public function test_unexpected_exceptions_do_not_expose_details(): void
    {
        $this->get('/__error-page-test/unexpected-exception')
            ->assertInternalServerError()
            ->assertSee('Something went wrong')
            ->assertDontSee('Sensitive exception details');
    }
}
