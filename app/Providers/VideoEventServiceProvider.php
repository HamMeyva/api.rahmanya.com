<?php

namespace App\Providers;

use App\Services\VideoEventService;
use App\Services\CacheService;
use Illuminate\Support\ServiceProvider;

class VideoEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('video.event', function ($app) {
            return new VideoEventService(
                $app->make(CacheService::class)
            );
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
