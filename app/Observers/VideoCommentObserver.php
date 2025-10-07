<?php

namespace App\Observers;

use App\Models\VideoComment;
use App\Jobs\UpdateVideoEngagementScore;
use App\Helpers\CommonHelper;

class VideoCommentObserver
{
    public function creating(VideoComment $model): void
    {
        if (!empty($model->comment)) {
            $mentions = (new CommonHelper)->parseMentions($model->comment);
            if ($mentions) {
                $model->mentions = $mentions;
            }
        }
    }

    public function updating(VideoComment $model): void
    {
        if ($model->isDirty('comment') && !empty($model->comment)) {
            $mentions = (new CommonHelper)->parseMentions($model->comment);
            if ($mentions) {
                $model->mentions = $mentions;
            }
        }
    }
    public function created(VideoComment $videoComment)
    {
        UpdateVideoEngagementScore::dispatch($videoComment->video_id);
    }

    public function deleted(VideoComment $videoComment)
    {
        UpdateVideoEngagementScore::dispatch($videoComment->video_id);
    }
}
