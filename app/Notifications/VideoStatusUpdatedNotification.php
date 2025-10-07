<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
 
class VideoStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_VIDEO_STATUS_UPDATED;

    public function __construct(protected string $videoId, protected string $videoGuid, protected $status)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "Durum: {$status}";
        $this->data = [
            'video_id' => $this->videoId,
            'video_guid' => $this->videoGuid,
            'status' => $this->status,
        ];
    }

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token && $notifiable->general_push_notification) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
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
