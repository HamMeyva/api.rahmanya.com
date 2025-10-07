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

class UserUnblockedFromStream implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stream;
    public $unblockedUserId;
    public $unblockedByUserId;

    public function __construct(AgoraChannel $stream, int $unblockedUserId, int $unblockedByUserId)
    {
        $this->stream = $stream;
        $this->unblockedUserId = $unblockedUserId;
        $this->unblockedByUserId = $unblockedByUserId;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("live-stream.{$this->stream->id}")
        ];
    }

    public function broadcastAs()
    {
        return 'user.unblocked';
    }

    public function broadcastWith()
    {
        return [
            'stream_id' => $this->stream->id,
            'unblocked_user_id' => $this->unblockedUserId,
            'unblocked_by_user_id' => $this->unblockedByUserId,
            'unblocked_at' => now()->toIso8601String()
        ];
    }
}
