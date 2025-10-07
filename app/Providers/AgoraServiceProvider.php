<?php

namespace App\Providers;

use App\Services\AgoraService;
use App\Services\AgoraTokenService;
use Illuminate\Support\ServiceProvider;

class AgoraServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AgoraTokenService::class, function ($app) {
            return new AgoraTokenService();
        });

        $this->app->singleton(AgoraService::class, function ($app) {
            return new AgoraService($app->make(AgoraTokenService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
