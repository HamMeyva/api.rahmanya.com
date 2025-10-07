<?php

namespace App\Jobs;

use Exception;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\VideoScoringService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateVideoEngagementScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(public string $videoId, public bool $updateTrending = true)
    {}

    public function handle()
    {
        try {
            Log::info('Updating video engagement score Job: ' . $this->videoId);
            $scoringService = app(VideoScoringService::class);
            $result = $scoringService->calculateAndUpdateScores($this->videoId, $this->updateTrending);
            
            if (!$result) {
                Log::warning('Failed to update video engagement score Job', [
                    'video_id' => $this->videoId
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error in UpdateVideoEngagementScore Job', [
                'error' => $e->getMessage(),
                'video_id' => $this->videoId
            ]);
            throw $e;
        }
    }
 
    public function failed(Throwable $exception)
    {
        Log::error('UpdateVideoEngagementScore job failed after retries', [
            'video_id' => $this->videoId,
            'error' => $exception->getMessage()
        ]);
    }
}
