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
use App\Infrastructure\GameServer\TcpGameServerClient;
use App\Infrastructure\Panfu\Repositories\DatabasePlayerRepository;
use App\Infrastructure\Panfu\Repositories\JsonShopRepository;
use App\Infrastructure\Panfu\Repositories\StaticFlashClientRepository;
use App\Infrastructure\Panfu\Repositories\StaticLandingPageRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LandingPageRepository::class, StaticLandingPageRepository::class);
        $this->app->bind(FlashClientRepository::class, StaticFlashClientRepository::class);
        $this->app->bind(PlayerRepository::class, DatabasePlayerRepository::class);
        $this->app->bind(ShopRepository::class, JsonShopRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
        $this->app->bind(GameServerClient::class, TcpGameServerClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
