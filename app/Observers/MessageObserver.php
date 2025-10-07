<?php

namespace App\Observers;

use App\Models\Chat\Message;

use App\Helpers\CommonHelper;

class MessageObserver
{
    public function creating(Message $model): void
    {
        if (!empty($model->content)) {
            $mentions = (new CommonHelper)->parseMentions($model->content);
            if ($mentions) {
                $model->mentions = $mentions;
            }
        }
    }

    public function updating(Message $model): void
    {
        if ($model->isDirty('content') && !empty($model->content)) {
            $mentions = (new CommonHelper)->parseMentions($model->content);
            if ($mentions) {
                $model->mentions = $mentions;
            }
        }
    }
}
