<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Models\VideoLike;
use App\Models\VideoComment;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateActiveUserMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Batch size for processing users
     *
     * @var int
     */
    protected $batchSize = 100;

    /**
     * VideoService instance
     * 
     * @var VideoService
     */
    protected $videoService;

    /**
     * Create a new job instance.
     *
     * @param VideoService $videoService
     * @return void
     */
    public function __construct(VideoService $videoService = null)
    {
        $this->videoService = $videoService;
    }

    /**
     * Execute the job.
     *
     * @param VideoService $videoService
     * @return void
     */
    public function handle(VideoService $videoService)
    {
        // If VideoService wasn't injected in constructor, use the one injected here
        $this->videoService = $videoService ?? $this->videoService;
        
        Log::info('Starting UpdateActiveUserMetricsJob');
        
        try {
            // Get users active in the past 24 hours
            $recentlyActiveUsers = $this->getRecentlyActiveUsers(1); // 1 day
            
            $totalUsers = count($recentlyActiveUsers);
            $processedUsers = 0;
            
            Log::info("Found {$totalUsers} users active in the past 24 hours to process");
            
            // Process users in batches
            foreach (array_chunk($recentlyActiveUsers, $this->batchSize) as $userBatch) {
                foreach ($userBatch as $userId) {
                    $this->updateUserMetrics($userId);
                    $processedUsers++;
                }
                
                Log::info("Processed {$processedUsers}/{$totalUsers} users' metrics");
            }
            
            // Also update relevant videos for these users
            $this->updateRelevantVideos($recentlyActiveUsers);
            
            Log::info('UpdateActiveUserMetricsJob completed successfully');
        } catch (\Exception $e) {
            Log::error('UpdateActiveUserMetricsJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get list of recently active users
     *
     * @param int $days Number of days to look back
     * @return array
     */
    protected function getRecentlyActiveUsers($days = 1)
    {
        // Get users who have viewed, liked or commented on videos in the past N days
        $activeUserIds = [];
        $lookBack = now()->subDays($days);
        
        // From video views
        $viewerIds = VideoView::where('viewed_at', '>=', $lookBack)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();
            
        $activeUserIds = array_merge($activeUserIds, $viewerIds);
        
        // From video likes
        $likerIds = VideoLike::where('created_at', '>=', $lookBack)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();
            
        $activeUserIds = array_merge($activeUserIds, $likerIds);
        
        // From video comments
        $commenterIds = VideoComment::where('created_at', '>=', $lookBack)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();
            
        $activeUserIds = array_merge($activeUserIds, $commenterIds);
        
        // Remove duplicates and ensure we have string IDs
        $uniqueUserIds = array_unique($activeUserIds);
        $processedUserIds = [];
        
        foreach ($uniqueUserIds as $id) {
            $processedUserIds[] = strval($id);
        }
        
        return $processedUserIds;
    }
    
    /**
     * Update metrics for a specific user
     *
     * @param string $userId
     * @return void
     */
    protected function updateUserMetrics($userId)
    {
        try {
            // Update user's cached interactions
            $this->updateUserInteractions($userId);
            
            // Update user's following feed cache
            $this->refreshUserFeedCache($userId);
            
            // Clear any stale caches
            $this->clearStaleCaches($userId);
            
            Log::info("Updated metrics for user {$userId}");
        } catch (\Exception $e) {
            Log::error("Error updating metrics for user {$userId}", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update cached user interactions
     *
     * @param string $userId
     * @return void
     */
    protected function updateUserInteractions($userId)
    {
        // Get users the current user has interacted with
        $interactionKey = "user_interactions:{$userId}";
        
        try {
            // Get recent interactions (past 30 days)
            $interactedUserIds = $this->videoService->getUsersWithInteractions($userId, 200, false);
            
            // Store in cache with 24-hour expiry
            Cache::put($interactionKey, $interactedUserIds, 60 * 24);
            
            Log::info("Updated interaction cache for user {$userId} with " . count($interactedUserIds) . " users");
        } catch (\Exception $e) {
            Log::error("Error updating interactions for user {$userId}", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Refresh user's feed cache
     *
     * @param string $userId
     * @return void
     */
    protected function refreshUserFeedCache($userId)
    {
        try {
            // Attempt to find the user
            $user = User::find($userId);
            
            if (!$user) {
                Log::warning("User {$userId} not found for feed cache refresh");
                return;
            }
            
            // Clear existing feed caches for this user
            $feedCachePatterns = [
                "video_feed_personalized_{$userId}_*",
                "video_feed_following_{$userId}_*",
                "sport_feed:{$userId}:*"
            ];
            
            foreach ($feedCachePatterns as $pattern) {
                Cache::forget($pattern);
            }
            
            // Pre-generate first page of each feed type for faster initial loads
            // This is done in a separate job to avoid blocking this job
            PreGenerateUserFeedsJob::dispatch($user);
            
            Log::info("Refreshed feed cache for user {$userId}");
        } catch (\Exception $e) {
            Log::error("Error refreshing feed cache for user {$userId}", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear any stale caches for the user
     *
     * @param string $userId
     * @return void
     */
    protected function clearStaleCaches($userId)
    {
        // Clear any stale caches related to this user
        $staleCachePatterns = [
            "relevant_users:{$userId}" // This should be regenerated when needed
        ];
        
        foreach ($staleCachePatterns as $pattern) {
            Cache::forget($pattern);
        }
    }
    
    /**
     * Update relevant videos for a set of users
     *
     * @param array $userIds
     * @return void
     */
    protected function updateRelevantVideos($userIds)
    {
        try {
            // Get videos interacted with by these users in last 24 hours
            $lookBack = now()->subDay();
            
            // Get videos viewed by these users
            $viewedVideoIds = VideoView::whereIn('user_id', $userIds)
                ->where('viewed_at', '>=', $lookBack)
                ->pluck('video_id')
                ->toArray();
                
            // Get videos liked by these users
            $likedVideoIds = VideoLike::whereIn('user_id', $userIds)
                ->where('created_at', '>=', $lookBack)
                ->pluck('video_id')
                ->toArray();
                
            // Get videos commented on by these users
            $commentedVideoIds = VideoComment::whereIn('user_id', $userIds)
                ->where('created_at', '>=', $lookBack)
                ->pluck('video_id')
                ->toArray();
                
            // Combine all video IDs
            $videoIds = array_unique(array_merge($viewedVideoIds, $likedVideoIds, $commentedVideoIds));
            
            // Limit to a reasonable number (top 500)
            $videoIds = array_slice($videoIds, 0, 500);
            
            if (empty($videoIds)) {
                return;
            }
            
            // Update trending scores for these videos
            BatchUpdateVideoTrendingScores::dispatch($videoIds);
            
            Log::info("Queued trending score updates for " . count($videoIds) . " videos");
        } catch (\Exception $e) {
            Log::error("Error updating relevant videos", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
