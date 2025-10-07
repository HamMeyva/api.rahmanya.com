<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserBlockedFromStream implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stream;
    public $blockedUserId;
    public $blockedByUserId;

    public function __construct(AgoraChannel $stream, int $blockedUserId, int $blockedByUserId)
    {
        $this->stream = $stream;
        $this->blockedUserId = $blockedUserId;
        $this->blockedByUserId = $blockedByUserId;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("live-stream.{$this->stream->id}")
        ];
    }

    public function broadcastWith()
    {
        return [
            'stream_id' => $this->stream->id,
            'blocked_user_id' => $this->blockedUserId,
            'blocked_by_user_id' => $this->blockedByUserId,
            'blocked_at' => now()->toIso8601String()
        ];
    }
}
