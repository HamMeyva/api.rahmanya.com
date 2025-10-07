<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FirebaseNotificationService;

class FcmChannel
{

    public function __construct(protected FirebaseNotificationService $firebaseNotificationService) {}

    public function send(object $notifiable, Notification $notification): bool
    {
        $fcmData = $notification->toFcm($notifiable);
        
        $data = $fcmData['data'] ?? [];
        $data['notification_type'] = $fcmData['notification_type'] ?? null;
         
        $this->firebaseNotificationService->sendToDevice(
            $notifiable->fcm_token,
            $fcmData['title'] ?? "",
            $fcmData['body'] ?? "",
            $data,
        );

        return true;
    }
}
