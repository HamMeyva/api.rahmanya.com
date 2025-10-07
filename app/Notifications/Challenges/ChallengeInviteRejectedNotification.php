<?php

namespace App\Notifications\Challenges;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Models\Challenge\ChallengeInvite;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Helpers\Variable;

class ChallengeInviteRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_CHALLENGE_INVITE_REJECTED;
    protected array $data;

    public function __construct(protected ChallengeInvite $invite, protected User $user)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $userName = $this->user->nickname ?? 'Bilinmeyen';
        $this->body = "{$userName} tarafından meydan okuma reddedildi.";
        $this->data = [
            'invite_id' => $this->invite->_id,
        ];
    }

    public function via($notifiable): array
    {
        $channels = ['broadcast'];

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
