<?php

namespace App\Notifications\LiveStream;

use App\Models\User;
use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use App\Models\Agora\AgoraChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LiveStreamStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_LIVE_STREAM_STARTED;
    protected array $data;

    public function __construct(protected AgoraChannel $agoraChannel)
    {
        $user = User::find($this->agoraChannel->user_id);
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = $user->nickname ?? 'Bilinmeyen' . " az önce canlı yayına başladı!";
        $this->data = [
            'agora_channel_id' => $this->agoraChannel->id,
            'title' => $this->agoraChannel->title,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
            ]
        ];
    }


    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token && $notifiable->accept_push) {
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

    public function toDatabase($notifiable): array
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
