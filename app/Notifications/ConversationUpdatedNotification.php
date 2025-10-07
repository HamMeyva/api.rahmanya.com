<?php

namespace App\Notifications;

use App\Helpers\Variable;
use App\Models\Chat\Message;
use Illuminate\Bus\Queueable;
use App\Models\Chat\Conversation;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ConversationUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_CONVERSATION_UPDATED;

    public function __construct(protected Conversation $conversation, protected ?Message $lastMessage = null)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];

        $senderFullName = 'Kullanıcı';
        if ($this->lastMessage) {
            $sender = $this->lastMessage->sender();
            if ($sender) {
                $senderFullName = $sender->fullName;
            }
        }

        $body = '';
        switch ($this->lastMessage->type) {
            case 'text':
                $body = $this->lastMessage->content;
                break;
            case 'image':
                $body = '📷 Fotoğraf gönderdi';
                break;
            case 'video':
                $body = '🎥 Video gönderdi';
                break;
            case 'audio':
                $body = '🎵 Ses gönderdi';
                break;
            case 'gif':
                $body = '🎬 GIF gönderdi';
                break;
            case 'shared_video':
                $body = '🎥 Video şutladı';
                break;
            case 'shared_profile':
                $body = '👤 Profil şutladı';
                break;
            default:
                $body = 'Yeni mesaj gönderdi';
        }

        $this->body = "{$senderFullName}: {$body}";
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
            'data' => [
                'conversation_id' => $this->conversation->id,
                'last_message_id' => $this->lastMessage->id ?? null,
                'last_message_content' => $this->lastMessage->content ?? null,
                'unread_count' => Message::query()
                    ->where('conversation_id', $this->conversation->id)
                    ->where('sender_id', '!=', $notifiable->id)
                    ->where('is_read', false)->count(),
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
                'conversation_id' => $this->conversation->id,
                'last_message_id' => $this->lastMessage->id ?? null,
                'last_message_content' => $this->lastMessage->content ?? null,
                'unread_count' => Message::query()
                    ->where('conversation_id', $this->conversation->id)
                    ->where('sender_id', '!=', $notifiable->id)
                    ->where('is_read', false)->count(),
            ]
        ]);
    }
}
