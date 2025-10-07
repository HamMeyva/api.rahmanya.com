<?php

namespace App\Providers;

use App\Services\NetGsmSmsService;
use Illuminate\Support\ServiceProvider;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Support\Facades\Notification;
use App\Services\FirebaseNotificationService;
use App\Notifications\Channels\DatabaseChannel;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseNotificationService::class, function ($app) {
            return new FirebaseNotificationService();
        });

        $this->app->singleton(NetGsmSmsService::class, function ($app) {
            return new NetGsmSmsService();
        });

        // Register aliases for easier access
        $this->app->alias(FirebaseNotificationService::class, 'firebase.notification');
        $this->app->alias(NetGsmSmsService::class, 'netgsm.sms');
        


        Notification::extend('fcm', function ($app) {
            return $app->make(FcmChannel::class);
        });
        Notification::extend('database', function ($app) {
            return $app->make(DatabaseChannel::class);
        });
        Notification::extend('sms', function ($app) {
            return $app->make(SmsChannel::class);
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
