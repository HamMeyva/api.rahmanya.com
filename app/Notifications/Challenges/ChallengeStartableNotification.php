<?php

namespace App\Notifications\Challenges;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Challenge\ChallengeInvite;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Helpers\Variable;

class ChallengeStartableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_CHALLENGE_STARTABLE;
    protected array $data;

    public function __construct(protected ChallengeInvite $invite, protected User $user)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "Meydan okumayı başlatabilirsiniz.";
        $this->data = [
            'agora_channel_id' => $this->invite->agora_channel_data['channel_id'] ?? null,
            'agora_channel_name' => $this->invite->agora_channel_data['channel_name'] ?? null,
            'invite_id' => $this->invite->_id,
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
