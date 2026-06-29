<?php

namespace App\Providers;

use App\Domain\Auth\Repositories\UserRepository;
use App\Domain\Panfu\Gateways\InformationServerGateway;
use App\Domain\Panfu\Repositories\FlashClientRepository;
use App\Domain\Panfu\Repositories\LandingPageRepository;
use App\Domain\Panfu\Repositories\LegacyPlayerRepository;
use App\Infrastructure\Auth\Repositories\EloquentUserRepository;
use App\Infrastructure\Panfu\Gateways\HttpInformationServerGateway;
use App\Infrastructure\Panfu\Repositories\MySqlLegacyPlayerRepository;
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
        $this->app->bind(InformationServerGateway::class, HttpInformationServerGateway::class);
        $this->app->bind(LegacyPlayerRepository::class, MySqlLegacyPlayerRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
