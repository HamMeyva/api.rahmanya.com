<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateVideoEngagementScore;

class UpdateVideoEngagementScores extends Command
{
    protected $signature = 'video:update-engagement-scores';

    protected $description = 'Update engagement scores for videos';

    public function handle()
    {
        $this->info("Starting engagement score update...");

        try {
            $oneHourAgo = Carbon::now()->subHour();
            $dispatchedCount = 0;

            Video::select('id')
             //   ->where('updated_at', '<', $oneHourAgo) eskilerden hatalı hesaplanan videolar oldugu için simdilik tüm videoları hesaplasın diye yoruma aldım yorum satırını kaldıralım daha sonra
                ->orderBy('created_at', 'desc')
                ->chunkById(1000, function ($videos) use (&$dispatchedCount) {
                    foreach ($videos as $video) {
                        UpdateVideoEngagementScore::dispatch($video->id);
                        $dispatchedCount++;
                    }
                });

            Log::info("UpdateVideoEngagementScores: Total videos dispatched to queue: {$dispatchedCount}");
            $this->info("Total videos dispatched to queue: {$dispatchedCount}");
        } catch (Exception $e) {
            $this->error("An error occurred while updating engagement scores: " . $e->getMessage());
            Log::error("UpdateVideoEngagementScores command failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
