<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MessageSentToFollowersNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_MESSAGE_SENT_TO_FOLLOWERS;

    public function __construct(protected User $sender, protected string $message)
    {
        $this->title = "{$this->sender->nickname} bir mesaj şutladı";
        $this->body = $message;
    }

    public function via($notifiable): array
    {
        $channels = ['broadcast', 'database'];

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
            'data' => [
                'sender_id' => $this->sender->id,
                'sender_nickname' => $this->sender->nickname,
                'sender_avatar' => $this->sender->avatar,
            ]
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => [
                'sender_id' => $this->sender->id,
                'sender_nickname' => $this->sender->nickname,
                'sender_avatar' => $this->sender->avatar,
            ]
        ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => [
                'sender_id' => $this->sender->id,
                'sender_nickname' => $this->sender->nickname,
                'sender_avatar' => $this->sender->avatar,
            ]
        ];
    }
}
