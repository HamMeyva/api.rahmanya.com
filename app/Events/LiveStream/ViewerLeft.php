<?php

namespace App\Events\LiveStream;

use App\Models\Agora\AgoraChannelViewer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViewerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected string $streamId, protected AgoraChannelViewer $agoraChannelViewer){}

    public function broadcastOn()
    {
        return new PrivateChannel("live-stream.{$this->streamId}");
    }

    public function broadcastWith()
    {
        return [
            'agora_channel_id' => $this->streamId,
            'user_id' => $this->agoraChannelViewer->user_id,
            'user_data' => $this->agoraChannelViewer->user_data,
            'viewing_duration' => $this->agoraChannelViewer->watch_duration,
            'left_at' => $this->agoraChannelViewer->left_at,
        ];
    }
}
