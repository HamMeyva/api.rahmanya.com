<?php

namespace App\Services;

use Ably\AblyRest;

class AblyNotificationService
{
    protected $ably;

    public function __construct()
    {
        $this->ably = new AblyRest(config('services.ably.key'));
    }

    public function sendNotification($channel, $publish, $data)
    {
        $this->ably->channels->get($channel)->publish($publish, $data);
        return true;
    }
}
