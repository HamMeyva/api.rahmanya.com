<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;
    public $isTyping;

    public function __construct($conversationId, $userId, $isTyping = true)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn()
    {
        return [new PrivateChannel("conversation.{$this->conversationId}")];
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'is_typing' => $this->isTyping,
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
