<?php

namespace App\Observers;

use App\Models\Video;
use App\Helpers\CommonHelper;

use Illuminate\Support\Facades\Cache;

class VideoObserver
{
    public function creating(Video $video): void
    {
        $video->views_count = 0;
        $video->play_count = 0;
        $video->likes_count = 0;
        $video->report_count = 0;
        $video->comments_count = 0;
        $video->completed_count = 0;


        if (!empty($video->description)) {
            $helper = new CommonHelper();

            // Mentions
            $mentions = $helper->parseMentions($video->description);
            if ($mentions) {
                $video->mentions = $mentions;
            }

            // Tags
            $tags = $helper->parseTags($video->description);
            if (!empty($tags)) {
                $video->tags = $tags;
            }
        }
    }

    public function created(Video $video): void
    {
        Cache::forget('dashboard:video-upload-chart');
    }

    public function updating(Video $video): void
    {
        if ($video->isDirty('description') && !empty($video->description)) {
            $helper = new CommonHelper();

            // Mentions
            $mentions = $helper->parseMentions($video->description);
            if ($mentions) {
                $video->mentions = $mentions;
            }

            // Tags
            $tags = $helper->parseTags($video->description);
            if (!empty($tags)) {
                $video->tags = $tags;
            }
        }
    }
}
