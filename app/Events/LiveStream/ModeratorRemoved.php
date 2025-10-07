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

class ModeratorRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AgoraChannel $stream, public int $moderatorId, public int $removedBy){}

    public function broadcastOn()
    {
        return [
            new PrivateChannel("live-stream.{$this->stream->id}"),
            new PrivateChannel("App.Models.User.{$this->moderatorId}")
        ];
    }

    public function broadcastAs()
    {
        return 'moderator.removed';
    }

    public function broadcastWith()
    {
        return [
            'stream_id' => $this->stream->id,
            'moderator_id' => $this->moderatorId,
            'removed_by' => $this->removedBy,
            'removed_at' => now()->toIso8601String()
        ];
    }
}
