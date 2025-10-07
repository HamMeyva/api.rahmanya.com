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

class StreamUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stream;

    public $streamData;

    public function __construct(AgoraChannel $stream)
    {
        $this->stream = $stream;
        
        $this->streamData = [
            'id' => $stream->id,
            'title' => $stream->title,
            'description' => $stream->description,
            'thumbnail_url' => $stream->thumbnail_url,
            'category_id' => $stream->category_id,
            'tags' => $stream->tags,
            'status' => $stream->status,
            'is_featured' => (bool) $stream->is_featured,
            'viewer_count' => $stream->viewer_count,
            'total_likes' => $stream->total_likes,
            'total_gifts' => $stream->total_gifts,
            'updated_at' => now()->toIso8601String()
        ];
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("live-stream.{$this->stream->id}")
        ];
    }

    public function broadcastAs()
    {
        return 'stream.updated';
    }

    public function broadcastWith()
    {
        return $this->streamData;
    }
}
