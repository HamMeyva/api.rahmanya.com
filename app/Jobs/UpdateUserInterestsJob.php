<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Models\VideoComment;
use App\Models\UserInterest;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateUserInterestsJob implements ShouldQueue
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
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting UpdateUserInterestsJob');
        
        try {
            // Get recently active users (active in the last 7 days)
            $recentlyActiveUsers = $this->getRecentlyActiveUsers();
            
            $totalUsers = count($recentlyActiveUsers);
            $processedUsers = 0;
            
            Log::info("Found {$totalUsers} recently active users to process");
            
            // Process users in batches
            foreach (array_chunk($recentlyActiveUsers, $this->batchSize) as $userBatch) {
                foreach ($userBatch as $userId) {
                    $this->processUserInterests($userId);
                    $processedUsers++;
                }
                
                Log::info("Processed {$processedUsers}/{$totalUsers} users' interests");
            }
            
            Log::info('UpdateUserInterestsJob completed successfully');
        } catch (\Exception $e) {
            Log::error('UpdateUserInterestsJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get list of recently active users
     *
     * @return array
     */
    protected function getRecentlyActiveUsers()
    {
        // Get users who have viewed, liked or commented on videos in the past 7 days
        $activeUserIds = [];
        
        // From video views
        $viewerIds = VideoView::where('viewed_at', '>=', now()->subDays(7))
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();
            
        $activeUserIds = array_merge($activeUserIds, $viewerIds);
        
        // From video likes
        $likerIds = VideoLike::where('created_at', '>=', now()->subDays(7))
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();
            
        $activeUserIds = array_merge($activeUserIds, $likerIds);
        
        // From video comments
        $commenterIds = VideoComment::where('created_at', '>=', now()->subDays(7))
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
     * Process and update interests for a specific user
     *
     * @param string $userId
     * @return void
     */
    protected function processUserInterests($userId)
    {
        try {
            // Get the user's video interactions from the past month
            $oneMonthAgo = now()->subDays(30);
            
            // Get videos the user has interacted with
            $likedVideoIds = VideoLike::where('user_id', $userId)
                ->where('created_at', '>=', $oneMonthAgo)
                ->pluck('video_id')
                ->toArray();
                
            $viewedVideoIds = VideoView::where('user_id', $userId)
                ->where('viewed_at', '>=', $oneMonthAgo)
                ->pluck('video_id')
                ->toArray();
                
            $commentedVideoIds = VideoComment::where('user_id', $userId)
                ->where('created_at', '>=', $oneMonthAgo)
                ->pluck('video_id')
                ->toArray();
            
            // Combine all video IDs with weights (likes have more weight than views)
            $videoInteractions = [];
            
            foreach ($viewedVideoIds as $videoId) {
                if (!isset($videoInteractions[$videoId])) {
                    $videoInteractions[$videoId] = 0;
                }
                $videoInteractions[$videoId] += 1; // Base weight for a view
            }
            
            foreach ($commentedVideoIds as $videoId) {
                if (!isset($videoInteractions[$videoId])) {
                    $videoInteractions[$videoId] = 0;
                }
                $videoInteractions[$videoId] += 3; // Higher weight for a comment
            }
            
            foreach ($likedVideoIds as $videoId) {
                if (!isset($videoInteractions[$videoId])) {
                    $videoInteractions[$videoId] = 0;
                }
                $videoInteractions[$videoId] += 5; // Highest weight for a like
            }
            
            // Extract tags from videos and calculate tag weights
            $tagWeights = $this->calculateTagWeights($videoInteractions);
            
            // Store the user's interests
            $this->storeUserInterests($userId, $tagWeights);
            
            // Update the cache
            $cacheKey = "user_interests:{$userId}";
            Cache::put($cacheKey, $tagWeights, 60 * 24); // Cache for 24 hours
            
            Log::info("Updated interests for user {$userId} with " . count($tagWeights) . " tags");
        } catch (\Exception $e) {
            Log::error("Error updating interests for user {$userId}", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Calculate tag weights from user's video interactions
     *
     * @param array $videoInteractions
     * @return array
     */
    protected function calculateTagWeights($videoInteractions)
    {
        $tagWeights = [];
        
        // Return if no interactions
        if (empty($videoInteractions)) {
            return $tagWeights;
        }
        
        // Get videos with their tags
        $videoIds = array_keys($videoInteractions);
        $videos = \App\Models\Video::whereIn('_id', $videoIds)
            ->get(['_id', 'tags', 'team_tags']);
            
        // Process video tags
        foreach ($videos as $video) {
            $interactionWeight = $videoInteractions[$video->_id] ?? 1;
            
            // Process regular tags
            $tags = $video->tags ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }
            
            foreach ($tags as $tag) {
                if (!isset($tagWeights[$tag])) {
                    $tagWeights[$tag] = 0;
                }
                $tagWeights[$tag] += $interactionWeight;
            }
            
            // Process team tags
            $teamTags = $video->team_tags ?? [];
            if (!is_array($teamTags)) {
                $teamTags = [];
            }
            
            foreach ($teamTags as $tag) {
                $tagKey = 'team:' . $tag;
                if (!isset($tagWeights[$tagKey])) {
                    $tagWeights[$tagKey] = 0;
                }
                $tagWeights[$tagKey] += ($interactionWeight * 1.5); // Team tags have more weight
            }
        }
        
        // Normalize tag weights
        arsort($tagWeights);
        $tagWeights = array_slice($tagWeights, 0, 50); // Keep only top 50 tags
        
        return $tagWeights;
    }
    
    /**
     * Store user interests in database
     *
     * @param string $userId
     * @param array $tagWeights
     * @return void
     */
    protected function storeUserInterests($userId, $tagWeights)
    {
        // Delete all existing interests for user
        UserInterest::where('user_id', $userId)->delete();
        
        // Store new interests
        $interests = [];
        foreach ($tagWeights as $tag => $weight) {
            $interests[] = [
                'user_id' => $userId,
                'tag' => $tag,
                'weight' => $weight,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        if (!empty($interests)) {
            UserInterest::insert($interests);
        }
    }
}
