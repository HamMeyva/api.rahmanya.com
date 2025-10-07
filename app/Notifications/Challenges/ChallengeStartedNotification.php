<?php

namespace App\Notifications\Challenges;

use App\Models\Agora\AgoraChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Helpers\Variable;

class ChallengeStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_CHALLENGE_STARTED;
    protected array $data;

    public function __construct(protected AgoraChannel $stream)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = 'Meydan okuma başladı.';
        $this->data = [
            'agora_channel_id' => $this->stream->id,
            'agora_channel_name' => $this->stream->channel_name,
        ];
    }


    public function via($notifiable): array
    {
        $channels = ['broadcast', 'database'];

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

    public function toDatabase(): array
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
