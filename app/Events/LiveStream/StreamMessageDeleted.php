<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $streamId;

    /**
     * @var string
     */
    public $messageId;

    /**
     * @var int
     */
    public $userId;

    public function __construct(string $streamId, string $messageId, int $userId)
    {
        $this->streamId = $streamId;
        $this->messageId = $messageId;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("live-stream.{$this->streamId}.chat");
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.deleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'stream_id' => $this->streamId,
            'message_id' => $this->messageId,
            'deleted_by' => $this->userId,
            'deleted_at' => now()->toIso8601String()
        ];
    }
}
