<?php

namespace App\Jobs;

use App\Models\Story;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExpireStoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $storyId){}

    public function handle(): void
    {
        $story = Story::find($this->storyId);
        if ($story && !$story->is_expired && $story->expires_at->isPast()) {
            $story->is_expired = true;
            $story->save();
        }
    }
}
