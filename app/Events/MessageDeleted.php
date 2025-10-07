<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
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
            'type' => $this->message->type ?? 'text',
            'reactions' => $this->message->reactions ?? [],
            'is_typing' => $this->message->is_typing ?? false,
            'created_at' => $this->message->created_at->toDateTimeString(),
            'sender' => [
                'id' => $sender->id,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar
            ]
        ];
    }
}
