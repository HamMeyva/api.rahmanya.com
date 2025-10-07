<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("conversation.{$this->message->conversation_id}");
    }

    public function broadcastWith()
    {
        $sender = $this->message->sender();

        return [
            'id' => (string)$this->message->_id,
            'conversation_id' => (string)$this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'content' => $this->message->content,
            'type' => $this->message->type ?? 'text',
            'media_url' => $this->message->media_url ?? null,
            'thumbnail_url' => $this->message->thumbnail_url ?? null,
            'duration' => $this->message->duration ?? null,
            'is_read' => $this->message->is_read,
            'read_at' => $this->message->read_at ? $this->message->read_at : null,
            'reply_to' => $this->message->reply_to ?? null,
            'reactions' => $this->message->reactions ?? [],
            'is_typing' => $this->message->is_typing ?? false,
            'created_at' => $this->message->created_at,
            'sender' => [
                'id' => $sender->id,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar
            ]
        ];
    }
}
