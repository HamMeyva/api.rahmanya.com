<?php

namespace App\Events\LiveStream;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TopGiftSendersUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $channelId;
    public array $payload;

    public function __construct($channelId, $payload)
    {
        $this->channelId = $channelId;
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("live-stream.{$this->channelId}");
    }

    public function broadcastAs(): string
    {
        return 'TopGiftSendersUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'channel_id' => $this->channelId,
            'data' => $this->payload,
        ];
    }
}
