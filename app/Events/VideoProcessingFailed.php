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

class VideoProcessingFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $video;
    public $errorMessage;

    public function __construct(Video $video, ?string $errorMessage = null)
    {
        $this->video = $video;
        $this->errorMessage = $errorMessage ?? 'Video processing failed';
    }

  
    public function broadcastOn()
    {
        return new PrivateChannel("App.Models.User.{$this->video->user_id}");
    }
    
    public function broadcastWith()
    {
        return [
            'message' => '❌ Video yüklenirken bir hata oluştu!',
            'video_id' => $this->video->id,
            'video_guid' => $this->video->video_guid,
            'thumbnail_url' => $this->video->thumbnail_url,
            'error' => $this->errorMessage,
            'status' => 'failed',
            'processing_status' => $this->video->processing_status,
        ];
    }
}
