<?php

namespace App\Models\Traits;

use App\Models\Notification as MongoNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

trait UserRoutesNotificationsChannels
{
    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_token;
    }

    public function routeNotificationForSms(): string
    {
        return '905534196292'; //!!!!!! @todo numarayı düzenlersin sonra
    }

    public function routeNotificationForMail(Notification $notification): ?string
    {
        return 'info@kodfixer.com'; //!!! $this->email ?? null;
    }

    public function routeNotificationForDatabase(): string
    {
        return 'mongo';
    }
}
