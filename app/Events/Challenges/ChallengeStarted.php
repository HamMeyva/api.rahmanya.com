<?php

namespace App\Events\Challenges;

use App\Models\Agora\AgoraChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChallengeStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected AgoraChannel $stream){}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("agora-channel.{$this->stream->id}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'Meydan okuma başladı!',
            'stream_id' => $this->stream->id,
        ];
    }
}
