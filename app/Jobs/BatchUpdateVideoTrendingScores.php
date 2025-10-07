<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchUpdateVideoTrendingScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchSize;
    protected $maxVideos;
    protected $filter;

    /**
     * Create a new job instance.
     *
     * @param int $batchSize Number of videos to process in each batch
     * @param int $maxVideos Maximum number of videos to process in total
     * @param array $filter Additional filters to apply to the video query
     */
    public function __construct(int $batchSize = 100, int $maxVideos = 1000, array $filter = [])
    {
        $this->batchSize = $batchSize;
        $this->maxVideos = $maxVideos;
        $this->filter = $filter;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $processedCount = 0;
            $now = now();

            // Process videos in batches to avoid memory issues
            Video::when(!empty($this->filter), function($query) {
                    foreach ($this->filter as $key => $value) {
                        $query->where($key, $value);
                    }
                })
                ->orderBy('created_at', 'desc')
                ->limit($this->maxVideos)
                ->chunkById($this->batchSize, function ($videos) use (&$processedCount, $now) {
                    foreach ($videos as $video) {
                        // Calculate recency factor
                        $ageInHours = $now->diffInHours($video->created_at);
                        $recencyFactor = 1.0;
                        
                        if ($ageInHours > 24 && $ageInHours <= 168) { // between 1-7 days
                            $recencyFactor = 1.0 - (($ageInHours - 24) / 144);
                        } elseif ($ageInHours > 168) { // older than 7 days
                            $recencyFactor = 0.2;
                        }

                        // Get counts directly from the document
                        $likesCount = $video->likes_count ?? 0;
                        $commentsCount = $video->comments_count ?? 0;
                        $viewsCount = $video->views_count ?? 0;

                        // Calculate engagement score
                        $engagementScore = ($likesCount * 3) + ($commentsCount * 5) + ($viewsCount * 0.1);

                        // Calculate trending score
                        $trendingScore = $engagementScore * $recencyFactor;

                        // Update the video with new scores
                        $video->engagement_score = $engagementScore;
                        $video->trending_score = $trendingScore;
                        $video->save();

                        $processedCount++;
                    }

                    Log::info('Batch video trending scores updated', [
                        'batch_size' => count($videos),
                        'total_processed' => $processedCount
                    ]);
                });

            Log::info('Completed batch update of video trending scores', [
                'total_videos_processed' => $processedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error in batch updating video trending scores', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
