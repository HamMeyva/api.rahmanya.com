<?php

namespace App\Notifications\Channels;

use App\Services\Sms\Netgsm;
use Illuminate\Notifications\Notification;
use Exception;

class SmsChannel
{
    protected $netgsm;

    public function __construct(Netgsm $netgsm)
    {
        $this->netgsm = $netgsm;
    }

    /**
     * @throws Exception
     */
    public function send(object $notifiable, Notification $notification): bool
    {
        $message = $notification->toSms($notifiable);
        $numbers = $notifiable->routeNotificationFor('sms');

        return $this->netgsm->send($numbers, $message);
    }
}
