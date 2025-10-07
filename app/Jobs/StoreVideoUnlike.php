<?php

namespace App\Jobs;

use App\Models\VideoLike;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreVideoUnlike implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $likeId;

    /**
     * Create a new job instance.
     *
     * @param string $likeId
     */
    public function __construct($likeId)
    {
        $this->likeId = $likeId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $like = VideoLike::find($this->likeId);
        if ($like) {
            $like->delete();
        }
    }
}
