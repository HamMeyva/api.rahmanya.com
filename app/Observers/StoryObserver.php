<?php

namespace App\Observers;

use App\Models\Story;
use App\Jobs\ExpireStoryJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StoryObserver
{
    public function creating(Story $model)
    {
        Cache::forget('dashboard:story-activities-chart');
    }
    
    public function created(Story $model)
    {
        if ($model->expires_at && $model->id) {
            Log::info('Scheduling ExpireStoryJob', [
                'story_id' => $model->id,
                'expires_at' => $model->expires_at
            ]);
            ExpireStoryJob::dispatch($model->id)->delay($model->expires_at);
        } else {
            Log::error('Cannot schedule ExpireStoryJob - missing data', [
                'story_id' => $model->id ?? 'null',
                'expires_at' => $model->expires_at ?? 'null'
            ]);
        }
    }
}
