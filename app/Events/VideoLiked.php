<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoLiked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $videoLike;

    public $videoId;
   
    public function __construct($videoLike)
    {
        $this->videoLike = $videoLike;
        $this->videoId = $videoLike->video_id;
    }
}
