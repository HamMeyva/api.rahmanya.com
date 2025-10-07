<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannel;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamLiked implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $likeData;

    public function __construct(public AgoraChannel $stream, public User $user)
    {
        $this->likeData = [
            'stream_id' => $stream->id,
            'user_id' => $user->id,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar
            ],
            'total_likes' => $stream->total_likes,
            'liked_at' => now()->toIso8601String()
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel("live-stream.{$this->stream->id}.chat");
    }

    public function broadcastWith()
    {
        return $this->likeData;
    }
}