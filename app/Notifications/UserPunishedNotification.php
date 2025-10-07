<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use App\Models\UserPunishment;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class UserPunishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_USER_PUNISHED;

    public function __construct(protected UserPunishment $userPunishment)
    {
        $this->userPunishment->loadMissing('punishment');
        $punishment = $this->userPunishment->punishment;

        $this->title = Variable::NOTIFICATION_TYPE_USER_PUNISHED;
        $this->body =  "{$punishment?->category?->parent?->name} -{$punishment?->category?->name} kategorsinde {$punishment?->get_card_type} aldınız.";
        $this->data = [
            'user_punishment_id' => $this->userPunishment->id,
            'applied_at' => $this->userPunishment->applied_at,
            'expires_at' => $this->userPunishment->expires_at,

            'punishment_id' => $punishment?->id,
            'card_type' => $punishment?->get_card_type,
            'is_direct_punishment' => $punishment?->is_direct_punishment,
            'description' => $punishment?->description,
            'punishment_category_id' => $punishment?->category?->id,
            'punishment_category_name' => $punishment?->category?->name,
            'punishment_sub_category_id' => $punishment?->category?->parent_id,
            'punishment_sub_category_name' => $punishment?->category?->parent?->name,
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
