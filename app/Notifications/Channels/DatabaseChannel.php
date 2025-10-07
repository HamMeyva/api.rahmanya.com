<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Str;
use Illuminate\Notifications\Notification;
use App\Models\Notification as MongoNotification;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        $db = $notifiable->routeNotificationForDatabase();
        $data = [];
        if (method_exists($notification, 'toDatabase')) {
            $data = $notification->toDatabase($notifiable);
        }
        
        $notifiableId = $notifiable->getKey();

        if ($db === 'mongo') {
            MongoNotification::create([
                'type' => get_class($notification),
                'user_id' => $notifiableId,
                'notification_type' => $data['notification_type'] ?? null,
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'data' => $data['data'] ?? null,
            ]);
        } else {
            return DatabaseNotification::create([
                'id' => Str::uuid()->toString(),
                'type' => get_class($notification),
                'notifiable_id' => $notifiableId,
                'notifiable_type' => $notifiable->getMorphClass(),
                'notification_type' => $data['notification_type'] ?? null,
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'url' => $data['url'] ?? null,
                'data' => $data['data'] ?? null,
            ]);
        }
    }
}
