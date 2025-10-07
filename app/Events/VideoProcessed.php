<?php

namespace App\Events;

use App\Models\Video;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Video $video) {}

    public function broadcastOn()
    {
        return new PrivateChannel("App.Models.User.{$this->video->user_id}");
    }
    
    public function broadcastWith()
    {
        return [
            'id' => $this->video->id,
            'title' => $this->video->title,
            'thumbnail_url' => $this->video->thumbnail_url,
            'status' => 'processed'
        ];
    }
}
