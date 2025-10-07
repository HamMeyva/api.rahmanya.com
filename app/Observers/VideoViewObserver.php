<?php

namespace App\Observers;

use App\Models\VideoView;
use App\Jobs\UpdateVideoEngagementScore;

class VideoViewObserver
{
    public function created(VideoView $videoView)
    {
        UpdateVideoEngagementScore::dispatch($videoView->video_id);
    }

    public function deleted(VideoView $videoView)
    {
        UpdateVideoEngagementScore::dispatch($videoView->video_id);
    }
}
