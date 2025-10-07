<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Helpers\Variable;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VideoReadyForDownloadNotification extends Notification implements ShouldQueue
{ 
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_VIDEO_READY_FOR_DOWNLOAD;

    public function __construct(protected string $videoUrl)
    {
        $this->title = Variable::NOTIFICATION_TYPE_VIDEO_READY_FOR_DOWNLOAD;
        $this->body =  "Video indirilmeye hazır.";
        $this->data = [
            'video_url' => $this->videoUrl,
        ];
    }

    public function via($notifiable): array
    {
        $channels = ['broadcast'];

        if ($notifiable->fcm_token && $notifiable->general_push_notification) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toFcm($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ]);
    }
}
