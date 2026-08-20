<?php

namespace App\Providers;

use App\Domain\Account\Repositories\AccountRepository;
use App\Domain\Auth\Repositories\UserRepository;
use App\Domain\Panfu\Repositories\FlashClientRepository;
use App\Domain\Panfu\Repositories\LandingPageRepository;
use App\Domain\Panfu\Repositories\PlayerRepository;
use App\Domain\Panfu\Repositories\ShopRepository;
use App\Domain\Servers\GameServerClient;
use App\Infrastructure\Account\Repositories\EloquentAccountRepository;
use App\Infrastructure\Auth\Repositories\EloquentUserRepository;
use App\Infrastructure\GameServer\HttpGameServerClient;
use App\Infrastructure\Panfu\Repositories\DatabasePlayerRepository;
use App\Infrastructure\Panfu\Repositories\JsonShopRepository;
use App\Infrastructure\Panfu\Repositories\StaticFlashClientRepository;
use App\Infrastructure\Panfu\Repositories\StaticLandingPageRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->bind(LandingPageRepository::class, StaticLandingPageRepository::class);
        $this->app->bind(FlashClientRepository::class, StaticFlashClientRepository::class);
        $this->app->bind(PlayerRepository::class, DatabasePlayerRepository::class);
        $this->app->bind(ShopRepository::class, JsonShopRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
        $this->app->bind(GameServerClient::class, HttpGameServerClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('amf', fn (Request $request): Limit => Limit::perMinute(
            max(1, (int) config('panfu.amf.requests_per_minute', 240)),
        )->by($request->ip()));

        Vite::prefetch(concurrency: 3);
    }
}
