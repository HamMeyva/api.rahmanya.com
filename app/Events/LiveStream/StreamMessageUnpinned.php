<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamMessageUnpinned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $streamId;

    /**
     * @var int
     */
    public $unpinnedBy;

    /**
     * Create a new event instance.
     *
     * @param string $streamId
     * @param int $unpinnedBy
     */
    public function __construct(string $streamId, int $unpinnedBy)
    {
        $this->streamId = $streamId;
        $this->unpinnedBy = $unpinnedBy;
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

    public function broadcastWith()
    {
        return [
            'stream_id' => $this->streamId,
            'unpinned_by' => $this->unpinnedBy,
            'unpinned_at' => now()->toIso8601String()
        ];
    }
}
