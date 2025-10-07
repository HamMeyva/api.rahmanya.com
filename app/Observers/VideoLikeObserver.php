<?php

namespace App\Observers;

use App\Models\VideoLike;
use App\Jobs\UpdateVideoEngagementScore;

class VideoLikeObserver
{
    public function created(VideoLike $videoLike): void
    {
        UpdateVideoEngagementScore::dispatch($videoLike->video_id);
    }

    public function deleted(VideoLike $videoLike): void
    {
        UpdateVideoEngagementScore::dispatch($videoLike->video_id);
    }
}
