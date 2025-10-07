<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $conversationId, public $userId, public $messageId, public $readAt) {}

    public function broadcastOn()
    {
        return [new PrivateChannel("conversation.{$this->conversationId}")];
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'message_id' => $this->messageId,
            'read_at' => $this->readAt,
        ];
    }
}
