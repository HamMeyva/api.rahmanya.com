<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshFeedScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The maximum number of users to process per batch
     */
    protected int $batchSize;

    /**
     * Feed types to refresh
     */
    protected array $feedTypes;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchSize = 100, array $feedTypes = ['personalized', 'following', 'sport'])
    {
        $this->batchSize = $batchSize;
        $this->feedTypes = $feedTypes;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        Log::info('Starting RefreshFeedScoresJob', [
            'batch_size' => $this->batchSize,
            'feed_types' => $this->feedTypes
        ]);

        try {
            // Get all active users (users who have logged in recently)
            // Process in batches to avoid memory issues
            User::query()
                ->whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDays(30))
                ->orderBy('last_login_at', 'desc')
                ->chunk($this->batchSize, function ($users) {
                    $this->processUserBatch($users);
                });

            // Also clear any guest feeds to ensure they get regenerated with new scores
            $this->clearGuestFeeds();

            $duration = round((microtime(true) - $startTime), 2);
            Log::info('RefreshFeedScoresJob completed', [
                'duration_seconds' => $duration
            ]);
        } catch (\Exception $e) {
            Log::error('Error in RefreshFeedScoresJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process a batch of users
     */
    protected function processUserBatch($users): void
    {
        $videoService = app(VideoService::class);
        
        foreach ($users as $user) {
            try {
                // Clear feed caches for user to force regeneration with new scoring
                $videoService->clearFeedCaches($user->id, $this->feedTypes);
                
                // Queue separate jobs to regenerate feeds with the updated scores
                // This distributes the workload across multiple workers
                foreach ($this->feedTypes as $feedType) {
                    UpdateUserFeedsJob::dispatch($user->id, $feedType, 1, 10)
                        ->onQueue('low')
                        ->delay(now()->addSeconds(rand(5, 60))); // Randomize to avoid spikes
                }
                
                Log::info('Refreshed feed scores for user', [
                    'user_id' => $user->id,
                    'feed_types' => $this->feedTypes
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to refresh feed scores for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with next user
            }
        }
    }

    /**
     * Clear guest feed caches
     */
    protected function clearGuestFeeds(): void
    {
        try {
            // Clear general guest feed caches
            foreach ($this->feedTypes as $feedType) {
                $cachePattern = "feed:guest:{$feedType}:*";
                $keys = Cache::get($cachePattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                    
                    Log::info('Cleared guest feed caches', [
                        'feed_type' => $feedType,
                        'keys_count' => count($keys)
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear guest feed caches', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
