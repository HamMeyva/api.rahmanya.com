<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCommented
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $videoId;
    public function __construct(public $comment)
    {
        $this->videoId = $comment->video_id;
    }
}
