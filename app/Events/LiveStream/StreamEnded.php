<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AgoraChannel $stream){}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("live-stream.{$this->stream->id}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->stream->id,
            'channel_name' => $this->stream->channel_name
        ];
    }
}
