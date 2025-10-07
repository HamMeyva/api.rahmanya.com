<?php

namespace App\Events;

use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoProcessingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Video $video) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->video->user_id}")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => '✅ Video başarıyla yüklendi! 🎬',
            'video_id' => $this->video->id,
            'video_guid' => $this->video->video_guid ?? null,
            'video_url' => $this->video->video_url ?? null,
            'thumbnail_url' => $this->video->thumbnail_url ?? null,
            'status' => $this->video->status,
            'processing_status' => $this->video->processing_status,
        ];
    }
}
