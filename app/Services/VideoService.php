<?php

namespace App\Services;

use Exception;
use App\Models\Team;
use App\Models\User;
use App\Models\Ad\Ad;
use App\Models\Video;
use App\Models\UserBlock;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Models\AppSetting;
use MongoDB\BSON\ObjectId;
use App\Facades\VideoEvent;
use App\Services\AdService;
use App\Models\VideoComment;
use App\Models\VideoMetrics;
use App\Services\CacheService;
use MongoDB\Laravel\Collection;
use App\Jobs\UpdateUserFeedsJob;
use App\Services\BunnyCdnService;
use App\Jobs\UpdateProfileFeedJob;
use Illuminate\Support\Facades\DB;
use MongoDB\Client as MongoClient;
use App\Services\Video\FeedService;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateUserInterestsJob;
use App\Jobs\PreGenerateUserFeedsJob;
use App\Models\Demographic\Placement;
use App\Services\VideoScoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use App\Jobs\UpdateVideoEngagementScore;
use App\Services\PerformanceMetricsService;
use App\Jobs\BatchUpdateVideoTrendingScores;
use App\Services\Traits\VideoFeedHelperTrait;
use MongoDB\Laravel\Eloquent\Model as MongoModel;

class VideoService
{
    use VideoFeedHelperTrait;

    /**
     * Constants for cache TTLs and limits
     * Centralized for easier maintenance and consistency
     */
    const FEED_CACHE_TTL_MINUTES = 15;               // Regular feed cache TTL
    const HIGH_TRAFFIC_FEED_CACHE_TTL_MINUTES = 30;  // Cache TTL for high-traffic feeds (sport, trending)
    const PROFILE_FEED_CACHE_TTL_MINUTES = 20;       // Profile feed cache TTL
    const SEEN_VIDEOS_TTL_HOURS = 24;                // How long to track seen videos
    const SEEN_VIDEOS_MAX_COUNT = 500;               // Maximum number of seen videos to track per user
    const LOCAL_CACHE_TTL_SECONDS = 60;              // Local (in-memory) cache TTL

    /**
     * Cache key prefixes for different types of video-related caches
     * Using constants ensures consistency across the application
     */
    const CACHE_PREFIX_VIDEO = 'video';              // Individual video data
    const CACHE_PREFIX_VIDEO_LIKES = 'video_likes';  // Video likes
    const CACHE_PREFIX_VIDEO_COMMENTS = 'video_comments'; // Video comments
    const CACHE_PREFIX_FEED = 'feed';                // Video feeds
    const CACHE_PREFIX_PROFILE = 'profile';          // Profile-related data
    const CACHE_PREFIX_TAG = 'tag';                  // Tag-related data
    const CACHE_PREFIX_TEAM_TAG = 'team_tag';        // Team tag-related data
    const CACHE_PREFIX_LOCATION = 'location';        // Location-related data

    protected BunnyCdnService $bunnyService;
    protected CacheService $cacheService;
    protected ?PerformanceMetricsService $metricsService;
    protected VideoScoringService $scoringService;

    public function __construct(
        BunnyCdnService $bunnyService,
        ?CacheService $cacheService = null,
        ?PerformanceMetricsService $metricsService = null,
        ?VideoScoringService $scoringService = null
    ) {
        $this->bunnyService = $bunnyService;
        $this->cacheService = $cacheService ?? new CacheService();
        $this->metricsService = $metricsService ?? app(PerformanceMetricsService::class);
        $this->scoringService = $scoringService ?? new VideoScoringService();
    }

    /**
     * Add a video to the user's seen list
     * This helps avoid showing the same videos repeatedly
     *
     * @param string $userId User ID
     * @param string $videoId Video ID
     * @return void
     */
    protected function addVideoToSeenList(string $userId, string $videoId): void
    {
        try {
            // Use consistent cache key format
            $cacheKey = $this->formatCacheKey('user_seen_videos', $userId);
            $seenVideos = $this->cacheService->getFromTieredCache(
                $cacheKey,
                function () {
                    return [];
                },
                self::SEEN_VIDEOS_TTL_HOURS * 60 * 60,
                self::LOCAL_CACHE_TTL_SECONDS
            );

            // Add to seen list if not already in it
            if (!in_array($videoId, $seenVideos)) {
                // Cap the list at the defined max to prevent it from growing too large
                if (count($seenVideos) >= self::SEEN_VIDEOS_MAX_COUNT) {
                    array_shift($seenVideos); // Remove oldest
                }

                $seenVideos[] = $videoId;
                $this->cacheService->putInTieredCache(
                    $cacheKey,
                    $seenVideos,
                    self::SEEN_VIDEOS_TTL_HOURS * 60 * 60,
                    self::LOCAL_CACHE_TTL_SECONDS
                );
            }
        } catch (\Exception $e) {
            // Just log error but don't interrupt the flow
            Log::warning('Failed to track seen video', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'video_id' => $videoId
            ]);
        }
    }

    /**
     * Get list of videos the user has recently seen
     *
     * @param string $userId User ID
     * @return array List of video IDs
     */
    protected function getUserSeenVideos(string $userId): array
    {
        try {
            $cacheKey = $this->formatCacheKey('user_seen_videos', $userId);
            return $this->cacheService->getFromTieredCache(
                $cacheKey,
                function () {
                    return [];
                },
                self::SEEN_VIDEOS_TTL_HOURS * 60 * 60,
                self::LOCAL_CACHE_TTL_SECONDS
            ) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to get seen videos', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return [];
        }
    }

    /**
     * Process video metadata from client-side upload to BunnyCDN
     *
     * @param User $user User uploading the video
     * @param string $videoId BunnyCDN video ID
     * @param array $metadata Video metadata
     * @return array Response with video data
     */
    public function processVideoMetadata(User $user, string $videoId, array $metadata): array
    {
        try {
            // 1. Extract and process video metadata from BunnyCDN
            $videoMetadata = $this->extractVideoMetadata(
                $videoId,
                $metadata,
                $user
            );

            // 2. Store video data in MongoDB
            $video = $this->storeVideoData($videoMetadata, $user);

            // 3. MongoDB indekslerini oluştur/güncelle
            $this->ensureMongoDBIndexes();

            return [
                'success' => true,
                'message' => 'Video metadata processed successfully',
                'data' => [
                    'video' => $video,
                    'videoId' => $videoId,
                    'thumbnailUrl' => $video->thumbnail_url
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Video metadata processing failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Video metadata processing failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * MongoDB için gerekli indeksleri oluştur
     *
     * @return void
     */
    protected function ensureMongoDBIndexes(): void
    {
        try {
            // Video koleksiyonu için indeksler
            Video::raw(function ($collection) {
                $collection->createIndex(['user_id' => 1]);
                $collection->createIndex(['created_at' => -1]);
                $collection->createIndex(['views_count' => -1]);
                $collection->createIndex(['engagement_score' => -1]);
                $collection->createIndex(['trending_score' => -1]);
                $collection->createIndex(['tags' => 1]);
                $collection->createIndex(['team_tags' => 1]);
                $collection->createIndex(['is_private' => 1]);
            });

            // VideoLike koleksiyonu için indeksler
            VideoLike::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1, 'user_id' => 1], ['unique' => true]);
                $collection->createIndex(['created_at' => -1]);
            });

            // VideoComment koleksiyonu için indeksler
            VideoComment::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1, 'created_at' => -1]);
                $collection->createIndex(['user_id' => 1]);
                $collection->createIndex(['parent_id' => 1]);
            });

            // VideoView koleksiyonu için indeksler
            VideoView::raw(function ($collection) {
                $collection->createIndex(['video_id' => 1, 'user_id' => 1]);
                $collection->createIndex(['viewed_at' => -1]);
                $collection->createIndex(['completed' => 1]);
            });
        } catch (\Exception $e) {
            Log::error('MongoDB index creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract video metadata from BunnyCDN and user input
     *
     * @param string $videoId BunnyCDN video ID
     * @param array $metadata User-provided metadata
     * @param User $user User uploading the video
     * @return array Processed metadata
     */
    protected function extractVideoMetadata(string $videoId, array $metadata, User $user): array
    {
        // Extract technical metadata from BunnyCDN
        $cdnMetadata = $this->bunnyService->extractVideoMetadata($videoId);

        // Check if BunnyCDN metadata extraction was successful
        if (isset($cdnMetadata['success']) && $cdnMetadata['success'] === false) {
            throw new \Exception('Video bulunamadı: ' . ($cdnMetadata['message'] ?? 'BunnyCDN\'de video bulunamadı'));
        }

        // Process tags
        $tags = $metadata['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        $isSport = $metadata['is_sport'] ?? false;

        // Get user data for embedding
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username ?? $user->nickname,
            'nickname' => $user->nickname,
            'profile_photo_url' => $user->profile_photo_url,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        // Combine all metadata
        return [
            'video_guid' => $videoId,
            'user_id' => $user->id,
            'title' => $metadata['title'] ?? 'Untitled Video',
            'name' => $metadata['title'] ?? 'Untitled Video',
            'description' => $metadata['description'] ?? '',
            'tags' => $tags,
            'is_private' => $metadata['is_private'] ?? false,
            'is_commentable' => $metadata['is_commentable'] ?? true,
            'thumbnail_url' => $cdnMetadata['thumbnailUrl'] ?? null,
            'video_url' => $cdnMetadata['mp4Url'] ?? null,
            'duration' => $cdnMetadata['length'] ?? 0,
            'width' => $cdnMetadata['width'] ?? 0,
            'height' => $cdnMetadata['height'] ?? 0,
            'framerate' => $cdnMetadata['framerate'] ?? 0,
            'status' => $cdnMetadata['status'] ?? 'processing',
            'views_count' => 0,
            'play_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'category' => $metadata['category'] ?? 'sports',
            'location' => $metadata['location'] ?? '',
            'language' => $metadata['language'] ?? 'en',
            'content_rating' => $metadata['content_rating'] ?? 'general',
            'engagement_score' => 0,
            'trending_score' => $metadata['trending_score'] ?? 0,
            'is_sport' => $isSport,
            'visibility' => $metadata['is_private'] ? 'private' : 'public',
            'processing_status' => 'completed',
            // Store basic user info directly in the document
            'email' => $user->email,
            'phone' => $user->phone,
            // Embedded user data
            'user_data' => $userData,
        ];
    }

    /**
     * Store video data in MongoDB
     *
     * @param array $videoData Processed video data
     * @param User $user User uploading the video
     * @return Video Created video model
     */
    protected function storeVideoData(array $videoData, User $user): Video
    {
        // Create or update the video record
        $video = Video::updateOrCreate(
            [
                'video_guid' => $videoData['video_guid'],
                'user_id' => $user->id
            ],
            $videoData
        );

        return $video;
    }

    /**
     * Generate personalized video feed for a user
     * Uses tiered caching, seen video filtering, and BunnyCDN integration
     *
     * @param User|null $user User to generate feed for
     * @param array $options Feed options (pagination, filters, etc)
     * @return array Videos and pagination info
     */
    public function generatePersonalizedFeed($user, array $options = [])
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('feed_', true);
        // Pagination and cache options
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 10;
        $bypassCache = $options['bypass_cache'] ?? false; // Default to using cache
        $randomFactor = $options['random_factor'] ?? (rand(1, 10) / 100); // 0.01 - 0.10 random factor

        // Create specific and consistent cache key
        $cacheKey = $this->formatCacheKey('feed', $user?->id ?? 'guest', 'personalized', ['page' => $page, 'per_page' => $perPage]);

        $cacheTtl = self::FEED_CACHE_TTL_MINUTES; // minutes - balance between freshness and performance

        // Use tiered caching system (in-memory + Redis) for improved performance
        if ($bypassCache) {
            $cachedResult = $this->getFeedFromCache($cacheKey, function () {
                return null;
            }, $cacheTtl);
            if ($cachedResult) {
                Log::info('generatePersonalizedFeed returning from tiered cache', [
                    'user_id' => $user?->id ?? 'guest',
                    'page' => $page,
                    'video_count' => count($cachedResult['videos'] ?? [])
                ]);

                $ads = (app(AdService::class))->getAds($user, ['placement_ids' => [Placement::PLACEMENT_MIXED_FEED], 'per_page' => $perPage]);
                $cachedResult['ads'] = $ads;
                return $cachedResult;
            }
        }

        try {
            Log::info('generatePersonalizedFeed generating new feed', [
                'user_id' => $user?->id ?? 'guest',
                'options' => $options
            ]);

            // Determine target user (own feed or someone else's feed)
            $targetUser = $user;
            if (isset($options['user_id']) && $user && $options['user_id'] != $user->id) {
                $targetUser = User::find($options['user_id']);
                if (!$targetUser) {
                    Log::warning('Target user not found', ['user_id' => $options['user_id']]);
                    return [
                        'videos' => [],
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'has_more' => false,
                        'current_page' => (int) $page
                    ];
                }
                Log::info('Generating feed for specific user', ['target_user_id' => $targetUser->id]);
            }

            // Start with a base query for videos
            $query = Video::query();

            // Only include public videos and non-sport videos
            $query->where('is_private', false)->where('is_sport', false)->where('status', 'finished');

            if ($targetUser) {
                // Additional user-specific filters can be added here if needed
                try {
                    $blockedUserIds = [];

                    if (method_exists($targetUser, 'blocked_users')) {
                        $blockedUserIds = $targetUser->blocked_users()->pluck('blocked_id')->toArray();
                    }


                    if (!empty($blockedUserIds)) {
                        $query->whereNotIn('user_id', $blockedUserIds);
                        Log::info('Excluding videos from blocked users', [
                            'count' => count($blockedUserIds)
                        ]);
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to exclude blocked users', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Apply time-based decay for trending scores using VideoScoringService
            // This ensures older videos get less prominence in the feed
            $this->scoringService->applyTrendingScoreSort($query);

            // Apply additional randomization for personalized experience
            // Adding a slight randomness factor ensures feed diversity between users
            if (!empty($randomFactor) && $randomFactor > 0) {
                $randomizedSort = $this->applyRandomizedSorting($query, $randomFactor, $targetUser?->id);
            }

            // Get total count for pagination
            $total = $query->count();
            Log::info('Total personalized videos found', ['count' => $total]);

            // Paginate results
            $videos = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();


            $ads = (app(AdService::class))->getAds($targetUser, ['placement_ids' => [Placement::PLACEMENT_MIXED_FEED], 'per_page' => $perPage]);

            $result = [
                'videos' => $videos,
                'ads' => $ads,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($page * $perPage) < $total,
                'current_page' => (int) $page
            ];

            // Cache feed result and queue pre-generation of next page
            $this->cacheFeedAndQueuePreGeneration(
                $cacheKey,
                $result,
                $cacheTtl,
                $user,
                $page,
                $perPage,
                $total,
                'personalized'
            );

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('generatePersonalizedFeed', $startTime, [
                'user_id' => $user?->id ?? 'guest',
                'page' => $page,
                'per_page' => $perPage,
                'video_count' => count($result['videos']),
                'trace_id' => $traceId
            ]);

            return $result;
        } catch (Exception $e) {
            return $this->handleFeedError($e, $page, $perPage, $user?->id ?? 'guest', 'personalized');
        }
    }

    /**
     * Refresh user feeds
     * This should be called when a user's interests change, they follow/unfollow someone,
     * or when video statuses change that might affect their feeds
     *
     * Enhanced to handle all feed types comprehensively and optionally refresh profile feeds
     *
     * @param string $userId User ID to refresh feeds for
     * @param bool $publishEvent Whether to publish a refresh event to RabbitMQ
     * @param bool $refreshProfileFeeds Whether to also refresh profile feed caches
     * @param array $specificFeedTypes Optional array of specific feed types to refresh ('following', 'sport', etc)
     * @return bool Success status
     */
    public function refreshUserFeeds(string $userId, bool $publishEvent = true, bool $refreshProfileFeeds = false, array $specificFeedTypes = []): bool
    {
        try {
            // Try to publish event first for distributed processing
            if ($publishEvent) {
                try {
                    VideoEvent::publishUserFeedRefreshEvent($userId);

                    Log::info('Published user feed refresh event', [
                        'user_id' => $userId,
                    ]);

                    return true;
                } catch (Exception $e) {
                    Log::warning('Error publishing user feed refresh event, falling back to direct refresh', [
                        'error' => $e->getMessage(),
                        'user_id' => $userId
                    ]);
                    // Continue with direct refresh
                }
            }

            // Determine which feed types to refresh
            $feedTypes = !empty($specificFeedTypes)
                ? $specificFeedTypes
                : ['personalized', 'following', 'sport', 'user_own'];

            $keysCleared = $this->clearFeedCaches($userId, $feedTypes);

            // Also clear profile feeds if requested
            if ($refreshProfileFeeds) {
                $profilePattern = "feed:profile:*:{$userId}:*";
                $count = $this->cacheService->clearPatternFromTieredCache($profilePattern);
                $keysCleared += $count;

                Log::info('Cleared profile feed caches', [
                    'profile_user_id' => $userId,
                    'keys_cleared' => $count
                ]);
            }

            // Queue pre-generation of feeds
            try {
                $this->preGenerateUserFeeds($userId, $feedTypes, $refreshProfileFeeds);
            } catch (Exception $e) {
                Log::warning('Error in pre-generation of user feeds', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId
                ]);
                // Don't let pre-generation errors affect the overall operation
            }

            Log::info('Successfully refreshed user feeds', [
                'user_id' => $userId,
                'keys_cleared' => $keysCleared,
                'feed_types' => $feedTypes
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error refreshing user feeds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Clear feed caches for a user
     * Helper method for refreshUserFeeds
     *
     * @param string $userId User ID
     * @param array $feedTypes Feed types to clear
     * @return int Number of cache keys cleared
     */
    protected function clearFeedCaches(string $userId, array $feedTypes): int
    {
        $keysCleared = 0;
        $prefix = 'feed:';

        foreach ($feedTypes as $feedType) {
            $pattern = "{$prefix}{$feedType}:{$userId}:*";
            $count = $this->cacheService->clearPatternFromTieredCache($pattern);
            $keysCleared += $count;

            Log::info("Cleared {$feedType} feed cache", [
                'user_id' => $userId,
                'keys_cleared' => $count
            ]);
        }

        return $keysCleared;
    }

    /**
     * Intelligently invalidate caches related to a video
     * This method uses a targeted approach to only clear caches that are affected by changes to a video
     *
     * @param Video $video The video that was modified
     * @param string $operation The operation performed (create, update, delete)
     * @param array $affectedFields Optional array of fields that were modified (for updates)
     * @return array Statistics about the cache invalidation
     */
    public function invalidateVideoCaches(Video $video, string $operation, array $affectedFields = []): array
    {
        $startTime = microtime(true);
        $stats = [
            'operation' => $operation,
            'video_id' => $video->id,
            'cleared_keys' => 0,
            'affected_users' => [],
            'affected_feeds' => [],
        ];

        // Always clear the specific video cache
        $videoDetailCacheKey = $this->formatCacheKey(self::CACHE_PREFIX_VIDEO, $video->id);
        Cache::forget($videoDetailCacheKey);
        $stats['cleared_keys']++;

        // Get the video owner's user ID
        $userId = $video->user_id;
        $stats['affected_users'][] = $userId;

        // Clear user's profile videos cache
        $userVideosCacheKey = $this->formatCacheKey('user_videos', $userId);
        Cache::forget($userVideosCacheKey);
        $stats['cleared_keys']++;

        // Determine which feed types need to be cleared based on the operation and affected fields
        $feedTypesToClear = $this->determineFeedTypesToClear($operation, $affectedFields, $video);
        $stats['affected_feeds'] = $feedTypesToClear;

        // Clear relevant feed caches for the video owner
        if (!empty($feedTypesToClear)) {
            $clearedCount = $this->clearFeedCaches($userId, $feedTypesToClear);
            $stats['cleared_keys'] += $clearedCount;
        }

        // For video creation or deletion, we need to update followers' feeds
        if ($operation === 'create' || $operation === 'delete') {
            // Get followers who would see this video in their feed
            $followerIds = $this->getFollowerIds($userId);
            $stats['affected_users'] = array_merge($stats['affected_users'], $followerIds);

            // Clear following feed for each follower
            foreach ($followerIds as $followerId) {
                $clearedCount = $this->clearFeedCaches($followerId, ['following']);
                $stats['cleared_keys'] += $clearedCount;
            }
        }

        // Handle tag-related caches if the video has tags
        if (!empty($video->tags) && ($operation !== 'update' || in_array('tags', $affectedFields))) {
            $tagClearedCount = $this->clearTagRelatedCaches($video);
            $stats['cleared_keys'] += $tagClearedCount;
        }

        // Log cache invalidation statistics
        $stats['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        Log::info('Video cache invalidation completed', $stats);

        return $stats;
    }

    /**
     * Determine which feed types need to be cleared based on the operation and affected fields
     *
     * @param string $operation The operation performed (create, update, delete)
     * @param array $affectedFields Fields that were modified (for updates)
     * @param Video $video The video that was modified
     * @return array Array of feed types to clear
     */
    protected function determineFeedTypesToClear(string $operation, array $affectedFields, Video $video): array
    {
        // For create and delete operations, clear all feed types
        if ($operation === 'create' || $operation === 'delete') {
            return ['personalized', 'following', 'sport', 'trending'];
        }

        // For updates, be more selective based on what changed
        $feedTypesToClear = [];

        $criticalFields = [
            'is_private',
            'status',
            'is_sport',
            'is_featured',
            'tags',
            'team_tags',
            'trending_score',
            'engagement_score'
        ];

        // Check if any critical fields were modified
        $hasCriticalChanges = !empty(array_intersect($criticalFields, $affectedFields));

        if ($hasCriticalChanges) {
            $feedTypesToClear[] = 'personalized';

            // If sport-related fields changed, clear sport feed
            if (in_array('is_sport', $affectedFields) || in_array('team_tags', $affectedFields)) {
                $feedTypesToClear[] = 'sport';
            }

            // If trending-related fields changed, clear trending feed
            if (in_array('trending_score', $affectedFields) || in_array('engagement_score', $affectedFields)) {
                $feedTypesToClear[] = 'trending';
            }

            // If privacy changed, clear following feed
            if (in_array('is_private', $affectedFields) || in_array('status', $affectedFields)) {
                $feedTypesToClear[] = 'following';
            }
        }

        // For likes and comments changes, only clear personalized feed
        if (in_array('likes_count', $affectedFields) || in_array('comments_count', $affectedFields)) {
            $feedTypesToClear[] = 'personalized';
        }

        return array_unique($feedTypesToClear);
    }

    /**
     * Get IDs of users who follow the specified user
     *
     * @param string $userId User ID to get followers for
     * @return array Array of follower user IDs
     */
    protected function getFollowerIds(string $userId): array
    {
        try {
            // Get followers from the database
            $followers = DB::table('follows')
                ->where('followed_id', $userId)
                ->where('status', 'accepted')
                ->whereNull('deleted_at')
                ->pluck('follower_id')
                ->toArray();

            return $followers;
        } catch (Exception $e) {
            Log::error('Failed to get follower IDs', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Clear caches related to video tags
     * This method clears all caches that might be affected by changes to a video's tags
     *
     * @param Video $video The video with tags to clear caches for
     * @return int Number of cache keys cleared
     */
    public function clearTagRelatedCaches(Video $video): int
    {
        $keysCleared = 0;

        // Clear regular tags caches
        if (!empty($video->tags)) {
            foreach ($video->tags as $tag) {
                $cacheKey = $this->formatCacheKey(self::CACHE_PREFIX_TAG, $tag);
                if (Cache::forget($cacheKey)) {
                    $keysCleared++;
                }
            }
        }

        // Clear team tags caches
        if (!empty($video->team_tags)) {
            foreach ($video->team_tags as $tag) {
                $cacheKey = $this->formatCacheKey(self::CACHE_PREFIX_TEAM_TAG, $tag);
                if (Cache::forget($cacheKey)) {
                    $keysCleared++;
                }
            }
        }

        // Clear location cache if applicable
        if (!empty($video->location)) {
            $cacheKey = $this->formatCacheKey(self::CACHE_PREFIX_LOCATION, $video->location);
            if (Cache::forget($cacheKey)) {
                $keysCleared++;
            }
        }

        return $keysCleared;
    }

    /**
     * Pre-generate feeds for a user
     * This reduces latency for the first page load of each feed type
     *
     * @param string $userId User ID
     * @param array $feedTypes Feed types to pre-generate
     * @param bool $includeProfileFeed Whether to also pre-generate profile feed
     * @return void
     */
    public function preGenerateUserFeeds(string $userId, array $feedTypes = [], bool $includeProfileFeed = false): void
    {
        // If feed types not specified, pre-generate all
        if (empty($feedTypes)) {
            $feedTypes = ['personalized', 'following', 'sport', 'user_own'];
        }

        try {
            // Find user
            $user = \App\Models\User::find($userId);
            if (!$user) {
                \Log::warning('User not found for feed pre-generation', ['user_id' => $userId]);
                return;
            }

            \Log::info('Starting feed pre-generation', [
                'user_id' => $userId,
                'feed_types' => $feedTypes
            ]);

            // Generate first page of each feed type with staggered job delays
            // to prevent overwhelming the job queue
            foreach ($feedTypes as $index => $feedType) {
                $options = [
                    'page' => 1,
                    'per_page' => 15,
                    'bypass_cache' => true, // Force regeneration
                ];

                // Stagger jobs with 3-second intervals
                $delay = now()->addSeconds($index * 3);

                // Dispatch appropriate job based on feed type
                switch ($feedType) {
                    case 'following':
                        UpdateUserFeedsJob::dispatch($userId, 'following', 1, 15)
                            ->onQueue('low')
                            ->delay($delay);
                        break;
                    case 'sport':
                        UpdateUserFeedsJob::dispatch($userId, 'sport', 1, 15)
                            ->onQueue('low')
                            ->delay($delay);
                        break;
                    case 'user_own':
                        UpdateUserFeedsJob::dispatch($userId, 'user_own', 1, 15)
                            ->onQueue('low')
                            ->delay($delay);
                        break;
                    case 'personalized':
                        UpdateUserFeedsJob::dispatch($userId, 'personalized', 1, 15)
                            ->onQueue('low')
                            ->delay($delay);
                        break;
                }
            }

            // Pre-generate profile feed if requested
            if ($includeProfileFeed) {
                UpdateProfileFeedJob::dispatch($userId, 1, 15)
                    ->onQueue('low')
                    ->delay(now()->addSeconds(count($feedTypes) * 3));
            }

            \Log::info('Successfully queued feed pre-generation jobs', [
                'user_id' => $userId,
                'feed_types' => $feedTypes,
                'include_profile' => $includeProfileFeed
            ]);
        } catch (\Exception $e) {
            \Log::error('Error pre-generating user feeds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Generate a feed of videos from users the target user follows
     * Uses tiered caching, optimized queries, and event-driven architecture
     *
     * @param User $user User to generate feed for
     * @param array $options Feed options (pagination, filters, etc)
     * @return array Videos and pagination info
     */
    public function generateFollowingFeed(User $user, array $options = []): array
    {
        // Cache ve sayfalama seçeneklerini ayarla
        $page = max(1, intval($options['page'] ?? 1)); // Ensures page is at least 1
        $perPage = max(1, min(50, intval($options['per_page'] ?? 10))); // Limits per_page between 1-50
        $bypassCache = $options['bypass_cache'] ?? false;
        $randomFactor = $options['random_factor'] ?? (rand(1, 10) / 100); // 0.01 - 0.10 random factor
        $cacheKey = $this->formatCacheKey('feed', $user->id, 'following', ['page' => $page, 'per_page' => $perPage]);
        $cacheTtl = self::FEED_CACHE_TTL_MINUTES; // 15 dakika

        Log::info('generateFollowingFeed başladı', [
            'user_id' => $user->id,
            'options' => $options,
            'cache_key' => $cacheKey
        ]);

        if (!$bypassCache) {
            $cachedResult = $this->getFeedFromCache($cacheKey, function () {
                return null;
            }, $cacheTtl);
            if ($cachedResult) {
                // İstek sonrası cache güncelleme job'ını düşük öncelikle tetikle
                // Bu sayede bir sonraki istek için güncel veriler hazır olacak
                try {
                    UpdateUserFeedsJob::dispatch($user->id, 'following', $page, $perPage)
                        ->onQueue('low')
                        ->delay(now()->addSeconds(15)); // Biraz geciktir ki server yükü dengeli olsun
                } catch (Exception $e) {
                    // Job gönderilemese bile istek etkilenmesin
                    Log::warning('Failed to dispatch feed update job', [
                        'user_id' => $user->id,
                        'feed_type' => 'following'
                    ]);
                }

                Log::info('Following feed returned from cache', [
                    'user_id' => $user->id,
                    'video_count' => count($cachedResult['videos'])
                ]);


                $ads = (app(AdService::class))->getAds($user, $options);
                $cachedResult['ads'] = $ads;
                return $cachedResult;
            }
        }

        try {
            // Feed oluşturma işlevini tanımla
            $generateFeedFunction = function () use ($user, $page, $perPage, $randomFactor, $options) {
                // Takip edilen kullanıcıları ve aynı takımdaki kullanıcıları getir
                $relevantUserIds = $this->getRelevantUserIds($user);

                if (empty($relevantUserIds)) {
                    Log::info('No relevant users found for following feed', [
                        'user_id' => $user->id
                    ]);

                    return [
                        'videos' => [],
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                    ];
                }

                Log::info('Relevant users found for following feed', [
                    'user_id' => $user->id,
                    'count' => count($relevantUserIds)
                ]);

                // Sorgu başlat
                $query = Video::query();

                // Sadece ilgili kullanıcıların videolarını getir
                $query->whereIn('user_id', $relevantUserIds);

                // Ortak filtrelemeleri uygula
                // $query = $this->applyCommonFeedFilters($query, $user, false, true);

                $query->where('is_sport', false);
                $query->where('is_private', false);
                $query->where('status', 'finished');

                // İzlenmiş videoları tepeye koymak yerine sona koy
                $seenVideoIds = $this->getUserSeenVideos($user->id);
                if (!empty($seenVideoIds)) {
                    // Exclude seen videos from the results
                    $query->whereNotIn('_id', $seenVideoIds);

                    // Apply time-based decay for trending scores using VideoScoringService
                    // This ensures older videos get less prominence in the feed
                    $this->scoringService->applyTrendingScoreSort($query);

                    \Log::info('Applied trending score decay and excluded seen videos', [
                        'seen_count' => count($seenVideoIds)
                    ]);
                } else {
                    // Apply time-based decay for trending scores first
                    $this->scoringService->applyTrendingScoreSort($query);

                    // Then apply additional randomization for personalization
                    if (!empty($randomFactor) && $randomFactor > 0) {
                        $randomizedSort = $this->applyRandomizedSorting($query, $randomFactor, $user->id);
                    }
                }

                // Toplam video sayısını hesapla (sayfalama için)
                $total = $query->count();

                // Sayfalama yap
                $videos = $query->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();

                $adInterval = AppSetting::getSetting('ad_interval');
                $adsNeeded = floor($perPage / $adInterval);
                $options = [
                    'limit' => $adsNeeded,
                    'placement_ids' => [Placement::PLACEMENT_FOLLOWED_FEED],
                ];
                $ads = (app(AdService::class))->getAds($user, $options);

                Log::info('Following videos found', [
                    'count' => count($videos),
                    'total' => $total,
                    'ads_count' => count($ads)
                ]);

                // Sonuç yapısını oluştur
                return [
                    'videos' => $videos,
                    'ad_interval' => $adInterval,
                    'ads' => $ads,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                ];
            };

            // Feed'i oluştur
            $result = $generateFeedFunction();

            // Sonuçları cache'le ve sonraki sayfayı ön bellekte hazırla (gerekirse)
            $this->cacheFeedAndQueuePreGeneration(
                $cacheKey,
                $result,
                $cacheTtl,
                $user,
                $page,
                $perPage,
                $result['total'],
                'following'
            );

            Log::info('generateFollowingFeed completed', [
                'user_id' => $user->id,
                'video_count' => count($result['videos']),
                'is_empty' => empty($result['videos'])
            ]);

            return $result;
        } catch (Exception $e) {
            // Standart hata işleme kullan
            return $this->handleFeedError($e, $page, $perPage, $user->id, 'following');
        }
    }

    /**
     * Generate a feed of sport videos
     * Uses tiered caching, optimized queries, and randomization
     *
     * @param User|null $user User to generate feed for (optional, can be null for guests)
     * @param array $options Feed options (pagination, filters, etc)
     * @return array Videos and pagination info
     */
    public function generateSportFeed($user, array $options = []): array
    {
        // Cache ve sayfalama seçeneklerini ayarla
        $page = max(1, intval($options['page'] ?? 1)); // Ensures page is at least 1
        $perPage = max(1, min(50, intval($options['per_page'] ?? 10))); // Limits per_page between 1-50
        $bypassCache = $options['bypass_cache'] ?? false;
        $randomFactor = $options['random_factor'] ?? (rand(1, 10) / 100); // 0.01 - 0.10 random factor
        $cacheKey = $this->formatCacheKey('feed', $user?->id ?? 'guest', 'sport', ['page' => $page, 'per_page' => $perPage]);
        $cacheTtl = self::HIGH_TRAFFIC_FEED_CACHE_TTL_MINUTES; // 30 dakika

        Log::info('generateSportFeed başladı', [
            'user_id' => $user?->id ?? 'guest',
            'options' => $options,
            'cache_key' => $cacheKey
        ]);

        // Cache'den almayı dene (eğer bypassCache değilse)
        if (!$bypassCache) {
            $cachedResult = $this->getFeedFromCache($cacheKey, function () {}, $cacheTtl);
            if ($cachedResult) {
                // Kullanıcı girişi yapmışsa istek sonrası cache güncelleme job'ını tetikle
                if ($user) {
                    try {
                        UpdateUserFeedsJob::dispatch($user->id, 'sport', $page, $perPage)
                            ->onQueue('low')
                            ->delay(now()->addSeconds(20)); // Biraz geciktir ki server yükü dengeli olsun
                    } catch (Exception $e) {
                        // Job gönderilemese bile istek etkilenmesin
                        Log::warning('Failed to dispatch feed update job', [
                            'user_id' => $user->id,
                            'feed_type' => 'sport'
                        ]);
                    }
                }

                Log::info('Sport feed returned from cache', [
                    'user_id' => $user?->id ?? 'guest',
                    'video_count' => count($cachedResult['videos'])
                ]);

                $ads = (app(AdService::class))->getAds($user, $options);
                $cachedResult['ads'] = $ads;
                return $cachedResult;
            }
        }

        try {
            // Feed oluşturma işlevini tanımla
            $generateFeedFunction = function () use ($user, $page, $perPage, $randomFactor, $options) {
                // Sorgu başlat
                $query = Video::query();

                // Sadece spor videoları getir
                $query->where('is_sport', true);

                $query->where('is_private', false);

                $query->where('status', 'finished');

                // Ortak filtrelemeleri uygula
                //$query = $this->applyCommonFeedFilters($query, $user, false, true);

                // Apply time-based decay for trending scores using VideoScoringService
                // This ensures older videos get less prominence in the feed
                $this->scoringService->applyTrendingScoreSort($query);

                // Apply additional randomization for personalized experience
                // Adding a slight randomness factor ensures feed diversity between users
                if (!empty($randomFactor) && $randomFactor > 0) {
                    $randomizedSort = $this->applyRandomizedSorting($query, $randomFactor, $user?->id);
                }

                // Toplam video sayısını hesapla (sayfalama için)
                $total = $query->count();

                // Sayfalama yap
                $videos = $query->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();

                Log::info('Sport videos found', [
                    'count' => count($videos),
                    'total' => $total
                ]);

                $adInterval = AppSetting::getSetting('ad_interval');
                $adsNeeded = floor($perPage / $adInterval);
                $options = [
                    'limit' => $adsNeeded,
                    'placement_ids' => [Placement::PLACEMENT_SPORT_FEED],
                ];
                $ads = (app(AdService::class))->getAds($user, $options);

                // Sonuç yapısını oluştur
                return [
                    'videos' => $videos,
                    'ad_interval' => $adInterval,
                    'ads' => $ads,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                ];
            };

            // Feed'i oluştur
            $result = $generateFeedFunction();

            // Sonuçları cache'le ve sonraki sayfayı ön bellekte hazırla (gerekirse)
            $this->cacheFeedAndQueuePreGeneration(
                $cacheKey,
                $result,
                $cacheTtl,
                $user,
                $page,
                $perPage,
                $result['total'],
                'sport'
            );

            \Log::info('generateSportFeed completed', [
                'user_id' => $user?->id ?? 'guest',
                'video_count' => count($result['videos']),
                'is_empty' => empty($result['videos'])
            ]);

            return $result;
        } catch (Exception $e) {
            // Standart hata işleme kullan
            return $this->handleFeedError($e, $page, $perPage, $user?->id ?? 'guest', 'sport');
        }
    }

    /**
     * Like or unlike a video with RabbitMQ event integration
     *
     * @param string $videoId Video ID
     * @param string $userId User ID
     * @param bool $like True to like, false to unlike
     * @return bool Success status
     */
    public function likeVideo(string $videoId, string $userId, bool $like = true): bool
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('like_video_', true);
        try {
            // Publish video like event to RabbitMQ
            try {
                // Event-driven architecture: Publish event instead of direct DB update
                $result = VideoEvent::publishVideoLikeEvent($videoId, $userId, $like);
                \Log::info('Published video like event to RabbitMQ', [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'like' => $like
                ]);
                return $result;
            } catch (\Exception $e) {
                \Log::warning('Failed to publish video like event to RabbitMQ, falling back to direct DB update', [
                    'error' => $e->getMessage(),
                    'video_id' => $videoId
                ]);

                // Fallback to direct database update if RabbitMQ is unavailable
                if ($like) {
                    // Check if already liked
                    $existingLike = VideoLike::where('video_id', $videoId)
                        ->where('user_id', $userId)
                        ->first();

                    if (!$existingLike) {
                        // Create new like
                        VideoLike::create([
                            'video_id' => $videoId,
                            'user_id' => $userId,
                            'created_at' => now()
                        ]);

                        // Increment like count on video
                        Video::where('_id', $videoId)->increment('likes_count');
                    }
                } else {
                    // Remove like if exists
                    $deleted = VideoLike::where('video_id', $videoId)
                        ->where('user_id', $userId)
                        ->delete();

                    if ($deleted) {
                        // Decrement like count on video (ensure it doesn't go below 0)
                        Video::where('_id', $videoId)
                            ->where('likes_count', '>', 0)
                            ->decrement('likes_count');
                    }
                }

                // Clear cache for the video
                $this->cacheService->clearFromTieredCache("video:{$videoId}");

                // Dispatch job to update engagement score
                UpdateVideoEngagementScore::dispatch($videoId);

                // Periodically update user interests
                if (rand(1, 5) === 1) { // 20% chance to update
                    UpdateUserInterestsJob::dispatch()->delay(now()->addMinutes(5));
                }

                // Performans metriklerini kaydet
                $this->trackPerformanceMetrics('likeVideo', $startTime, [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'action' => $like ? 'like' : 'unlike',
                    'trace_id' => $traceId
                ]);

                return true;
            }
        } catch (\Exception $e) {
            // Gelişmiş hata ayıklama metodunu kullan - özellikle MongoDB/SQL çapraz hata durumlarında faydalı
            try {
                $this->handleEnhancedException($e, 'likeVideo', [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'action' => $like ? 'like' : 'unlike',
                    'trace_id' => $traceId
                ]);
            } catch (\Exception $ex) {
                // handleEnhancedException metodu da başarısız olursa, orijinal öntanımlı loglama davranışına geri dön
                \Log::error('Error in likeVideo', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'video_id' => $videoId,
                    'user_id' => $userId
                ]);
            }
            return false;
        }
    }

    /**
     * Add a comment to a video with RabbitMQ event integration
     *
     * @param string $videoId Video ID
     * @param string $userId User ID
     * @param string $comment Comment text
     * @param string|null $parentId Parent comment ID for replies
     * @return VideoComment|null Created comment or null on failure
     */
    public function addComment(string $videoId, string $userId, string $comment, ?string $parentId = null): ?VideoComment
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('comment_', true);
        try {
            // MongoDB/SQL ilişki hatalarını önlemek için öncelikle User'ın varlığını kontrol edelim
            $user = null;
            try {
                // Cross-database relationship hataları için doğrudan SQL kullanımı yerine find() kullanın
                $user = \App\Models\User::find($userId);
                if (!$user) {
                    \Log::warning('User not found for comment creation', [
                        'user_id' => $userId,
                        'video_id' => $videoId,
                        'trace_id' => $traceId
                    ]);
                }
            } catch (\Exception $userEx) {
                \Log::warning('Error finding user for comment', [
                    'error' => $userEx->getMessage(),
                    'user_id' => $userId,
                    'trace_id' => $traceId
                ]);
                // Kullanıcıyı bulamazsak bile yorumu ekleyebiliriz
            }

            // Create the comment in database
            $commentObj = VideoComment::create([
                'video_id' => $videoId,
                'user_id' => $userId,
                'parent_id' => $parentId,
                'comment' => $comment,
                'created_at' => now()
            ]);

            // Try to publish event to RabbitMQ
            try {
                // Event-driven architecture: Publish event for comment creation
                VideoEvent::publishVideoCommentEvent(
                    $videoId,
                    $userId,
                    (string) $commentObj->id,
                    $comment,
                    $parentId
                );

                \Log::info('Published comment event to RabbitMQ', [
                    'video_id' => $videoId,
                    'comment_id' => $commentObj->id
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to publish comment event to RabbitMQ', [
                    'error' => $e->getMessage(),
                    'video_id' => $videoId,
                    'comment_id' => $commentObj->id
                ]);

                // Fallback: Direct DB update if event publishing fails
                // Increment comment count on video
                Video::where('_id', $videoId)->increment('comments_count');

                // Dispatch job to update engagement score
                UpdateVideoEngagementScore::dispatch($videoId);

                // Periodically update user interests
                if (rand(1, 3) === 1) { // 33% chance to update
                    UpdateUserInterestsJob::dispatch()->delay(now()->addMinutes(5));
                }
            }

            // Clear cache for video
            $this->cacheService->clearFromTieredCache("video:{$videoId}");

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('addComment', $startTime, [
                'video_id' => $videoId,
                'user_id' => $userId,
                'has_parent' => !empty($parentId),
                'trace_id' => $traceId,
                'comment_length' => strlen($comment)
            ]);

            return $commentObj;
        } catch (\Exception $e) {
            // Cross-database ilişki hata yönetimi için gelişmiş hata yakalama
            try {
                $this->handleEnhancedException($e, 'addComment', [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'has_parent' => !empty($parentId),
                    'trace_id' => $traceId
                ]);

                // MongoDB ve SQL arasındaki ilişki hatalarını tespit et
                if (strpos($e->getMessage(), 'prepare() on null') !== false) {
                    \Log::critical('Cross-database relationship error in comment creation', [
                        'info' => 'This is likely due to MongoDB-SQL relationship issues',
                        'video_id' => $videoId,
                        'user_id' => $userId,
                        'suggestion' => 'Use direct User::find() instead of relationships'
                    ]);
                }
            } catch (\Exception $innerEx) {
                // Yönetilemeyen hata durumunda standart loglama
                \Log::error('Error in addComment', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'video_id' => $videoId,
                    'user_id' => $userId
                ]);
            }
            return null;
        }
    }

    /**
     * Format a consistent cache key
     * Ensures all cache keys follow the same format for easier management
     *
     * @param string $type Key type (e.g., 'feed', 'user_seen_videos')
     * @param string $identifier Primary identifier (user_id, video_id)
     * @param string|null $subType Optional sub-type (following, sport)
     * @param array $params Optional additional parameters (page, per_page)
     * @return string Formatted cache key
     */
    public function formatCacheKey(string $type, string $identifier, ?string $subType = null, array $params = []): string
    {
        $key = $type;

        if ($subType) {
            $key .= ":{$subType}";
        }

        $key .= ":{$identifier}";

        foreach ($params as $paramName => $paramValue) {
            $key .= ":{$paramName}:{$paramValue}";
        }

        return $key;
    }

    /**
     * Get users that the target user has interacted with their videos
     * This is used for personalization algorithms
     * Enhanced to provide better personalization and performance
     *
     * @param string $userId User ID
     * @param int $limit Maximum number of users to return
     * @param bool $useCache Whether to use cache
     * @return array User IDs that the user has interacted with, with weights
     */
    public function getUsersWithInteractions(string $userId, int $limit = 100, bool $useCache = true): array
    {
        $cacheKey = $this->formatCacheKey('user_interactions', $userId);
        $cacheTtl = 30; // 30 minutes - reduced to ensure fresher data for personalization

        // Try to get from cache first using tiered caching for better performance
        if ($useCache && $this->cacheService) {
            return $this->cacheService->getFromTieredCache($cacheKey, function () use ($userId, $limit) {
                return $this->fetchUserInteractionsFromDB($userId, $limit);
            }, $cacheTtl * 60, 60);
        }

        return $this->fetchUserInteractionsFromDB($userId, $limit);
    }

    /**
     * Fetch user interactions from database with improved algorithm
     * This method separates the database logic from the caching logic
     *
     * @param string $userId User ID
     * @param int $limit Maximum number of users to return
     * @return array User IDs that the user has interacted with, with weights
     */
    protected function fetchUserInteractionsFromDB(string $userId, int $limit): array
    {
        try {
            // Initialize arrays to store user IDs and their interaction weights
            $userInteractions = [];
            $recentInteractions = [];

            // Get recent interactions from VideoMetrics (last 7 days)
            // This is more efficient than querying multiple collections
            $recentDate = now()->subDays(7)->timestamp;
            $metrics = VideoMetrics::where('user_interactions.user_id', $userId)
                ->where('user_interactions.timestamp', '>=', $recentDate)
                ->limit($limit * 2) // Get more to ensure we have enough after filtering
                ->get();

            // Process recent interactions with higher weight
            foreach ($metrics as $metric) {
                if ($video = Video::find($metric->video_id)) {
                    $creatorId = $video->user_id;
                    if (!isset($recentInteractions[$creatorId])) {
                        $recentInteractions[$creatorId] = 0;
                    }

                    // Count interactions by type with different weights
                    foreach ($metric->user_interactions as $interaction) {
                        if ($interaction['user_id'] === $userId) {
                            switch ($interaction['type']) {
                                case 'like':
                                    $recentInteractions[$creatorId] += 3; // Likes are strong signals
                                    break;
                                case 'comment':
                                    $recentInteractions[$creatorId] += 5; // Comments are strongest signals
                                    break;
                                case 'view':
                                    $recentInteractions[$creatorId] += 1; // Views are weaker signals
                                    break;
                                case 'share':
                                    $recentInteractions[$creatorId] += 4; // Shares are strong signals
                                    break;
                            }
                        }
                    }
                }
            }

            // If we don't have enough recent interactions, get historical data
            if (count($recentInteractions) < ($limit / 2)) {
                // Get likes with higher weight (users whose videos the target user liked)
                $likedUserIds = VideoLike::where('user_id', $userId)
                    ->join('videos', 'video_likes.video_id', '=', 'videos._id')
                    ->select('videos.user_id', DB::raw('count(*) as interaction_count'))
                    ->groupBy('videos.user_id')
                    ->orderBy('interaction_count', 'desc')
                    ->limit($limit)
                    ->pluck('interaction_count', 'videos.user_id')
                    ->toArray();

                // Get comments with highest weight
                $commentedUserIds = VideoComment::where('user_id', $userId)
                    ->join('videos', 'video_comments.video_id', '=', 'videos._id')
                    ->select('videos.user_id', DB::raw('count(*) as interaction_count'))
                    ->groupBy('videos.user_id')
                    ->orderBy('interaction_count', 'desc')
                    ->limit($limit)
                    ->pluck('interaction_count', 'videos.user_id')
                    ->toArray();

                // Get views with lower weight
                $viewedUserIds = VideoView::where('user_id', $userId)
                    ->join('videos', 'video_views.video_id', '=', 'videos._id')
                    ->select('videos.user_id', DB::raw('count(*) as interaction_count'))
                    ->groupBy('videos.user_id')
                    ->orderBy('interaction_count', 'desc')
                    ->limit($limit)
                    ->pluck('interaction_count', 'videos.user_id')
                    ->toArray();

                // Combine all interactions with appropriate weights
                foreach ($likedUserIds as $uid => $count) {
                    if (!isset($userInteractions[$uid]))
                        $userInteractions[$uid] = 0;
                    $userInteractions[$uid] += $count * 3; // Weight for likes
                }

                foreach ($commentedUserIds as $uid => $count) {
                    if (!isset($userInteractions[$uid]))
                        $userInteractions[$uid] = 0;
                    $userInteractions[$uid] += $count * 5; // Weight for comments
                }

                foreach ($viewedUserIds as $uid => $count) {
                    if (!isset($userInteractions[$uid]))
                        $userInteractions[$uid] = 0;
                    $userInteractions[$uid] += $count * 1; // Weight for views
                }
            }

            // Combine recent and historical interactions, prioritizing recent ones
            foreach ($recentInteractions as $uid => $weight) {
                if (!isset($userInteractions[$uid]))
                    $userInteractions[$uid] = 0;
                $userInteractions[$uid] += $weight * 2; // Recent interactions get double weight
            }

            // Add slight randomization to avoid showing the same order every time
            // but still maintain overall weighting
            foreach ($userInteractions as $uid => $weight) {
                // Add ±10% random variation
                $randomFactor = mt_rand(90, 110) / 100;
                $userInteractions[$uid] = $weight * $randomFactor;
            }

            // Sort by weight and limit
            arsort($userInteractions);
            $weightedUserIds = array_slice($userInteractions, 0, $limit, true);

            // Log for monitoring purposes
            Log::debug('User interaction weights calculated', [
                'user_id' => $userId,
                'interaction_count' => count($weightedUserIds),
                'top_5_weights' => array_slice($weightedUserIds, 0, 5, true)
            ]);

            return $weightedUserIds;
        } catch (\Exception $e) {
            Log::error('Error in fetchUserInteractionsFromDB: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Update video status
     * Used by BunnyCDN webhook integration
{{ ... }}
     *
     * @param string $videoId Video ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateVideoStatus(string $videoId, string $status): bool
    {
        try {
            // Try to publish status update event to RabbitMQ
            try {
                VideoEvent::publishVideoStatusUpdateEvent($videoId, $status);
                \Log::info('Published video status update event', [
                    'video_id' => $videoId,
                    'status' => $status
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to publish status update event, falling back to direct update', [
                    'error' => $e->getMessage()
                ]);
                // Continue with direct update
            }

            $video = Video::find($videoId);
            if (!$video) {
                \Log::warning('Video not found for status update', ['video_id' => $videoId]);
                return false;
            }

            // Önceki durumu kaydet
            $previousStatus = $video->status;

            // Durumu güncelle
            $video->status = $status;
            $video->save();

            // Yeni akıllı önbellek temizleme sistemini kullan
            // Status değişikliği önemli bir değişiklik olduğu için 'update' operasyonu olarak işaretliyoruz
            $cacheStats = $this->invalidateVideoCaches($video, 'update', ['status']);

            // Durum değişikliği önemli bir değişiklik ise (özellikle finished veya available durumlarına geçiş)
            // daha kapsamlı önbellek temizliği yap
            if (in_array($status, ['finished', 'available', 'failed']) && $previousStatus !== $status) {
                // Video sahibinin feed'lerini yenile
                $this->refreshUserFeeds($video->user_id, true, true);

                // Video sahibinin takipçilerinin following feed'lerini yenile
                $followerIds = $this->getFollowerIds($video->user_id);
                foreach ($followerIds as $followerId) {
                    $this->refreshUserFeeds($followerId, false, false, ['following']);
                }

                \Log::info('Comprehensive cache refresh for status change', [
                    'video_id' => $videoId,
                    'previous_status' => $previousStatus,
                    'new_status' => $status,
                    'cache_stats' => $cacheStats,
                    'affected_followers' => count($followerIds)
                ]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error updating video status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'video_id' => $videoId
            ]);
            return false;
        }
    }

    /**
     * Update trending scores for videos
     * Uses RabbitMQ and batch processing for efficiency
     * Enhanced with improved scoring algorithm and normalization
     *
     * @param int $limit Maximum number of videos to update
     * @param bool $useQueue Whether to use queue for processing
     * @param array $specificVideoIds Optional array of specific video IDs to update
     * @return int Number of videos updated
     */
    public function updateTrendingScores(int $limit = 100, bool $useQueue = true, array $specificVideoIds = []): int
    {
        $startTime = microtime(true);
        $count = 0;

        try {
            // If specific IDs are provided, only update those
            if (!empty($specificVideoIds)) {
                // Event-driven: Try to publish batch update event
                try {
                    VideoEvent::publishBatchTrendingScoreUpdateEvent($specificVideoIds);
                    \Log::info('Published batch trending score update event', [
                        'count' => count($specificVideoIds)
                    ]);
                    return count($specificVideoIds);
                } catch (\Exception $e) {
                    // Fallback to direct processing if RabbitMQ fails
                    \Log::warning('Failed to publish trending score event, falling back to direct processing', [
                        'error' => $e->getMessage()
                    ]);
                    // Continue with direct processing below
                }
            }

            // Use queue for large batches
            if ($useQueue && count($specificVideoIds) > 10) {
                // Split into smaller batches for better performance
                $batches = array_chunk($specificVideoIds, 10);
                foreach ($batches as $index => $batch) {
                    BatchUpdateVideoTrendingScores::dispatch($batch)
                        ->onQueue('low')
                        ->delay(now()->addSeconds($index * 5)); // Stagger jobs to prevent overload
                }
                return count($specificVideoIds);
            } else {
                // Process immediately with improved algorithm
                $videos = [];
                $scores = [];

                // First pass: Calculate raw scores for all videos
                foreach ($specificVideoIds as $videoId) {
                    if ($video = Video::find($videoId)) {
                        // Calculate trending score based on engagement and recency
                        $recencyFactor = $this->calculateRecencyFactor($video->created_at);

                        // Get counts directly from the document to avoid relationship loading
                        $likesCount = $video->likes_count ?? 0;
                        $commentsCount = $video->comments_count ?? 0;
                        $viewsCount = $video->views_count ?? 0;
                        $playCount = $video->play_count ?? 0;
                        $sharesCount = $video->shares_count ?? 0;

                        // Enhanced engagement score formula with better weights
                        // Normalize to smaller values for more efficient storage
                        // Use logarithmic scaling for views to prevent popular videos from dominating
                        $normalizedViews = $viewsCount > 0 ? log10($viewsCount + 1) * 5 : 0;

                        // Use play_count as the second highest factor after comments
                        $normalizedPlayCount = $playCount > 0 ? log10($playCount + 1) * 20 : 0;

                        // Improved engagement formula with play_count as second highest factor after comments
                        $engagementScore = (
                            ($likesCount * 15) +
                            ($commentsCount * 25) +
                            $normalizedViews +
                            $normalizedPlayCount +
                            ($sharesCount * 18)
                        ) / 10; // Scale down for storage efficiency

                        // Enhanced trending score formula with time decay
                        $trendingScore = round($engagementScore * $recencyFactor, 2);

                        $videos[] = $video;
                        $scores[$video->id] = [
                            'engagement' => $engagementScore,
                            'trending' => $trendingScore
                        ];
                        $count++;
                    }
                }

                // Second pass: Update all videos efficiently
                foreach ($videos as $video) {
                    $video->trending_score = $scores[$video->id]['trending'];
                    $video->engagement_score = $scores[$video->id]['engagement'];
                    $video->save();

                    // Also update the VideoMetrics collection if it exists
                    VideoMetrics::updateFromVideo($video);
                }

                $duration = round((microtime(true) - $startTime) * 1000, 2);
                \Log::info('Updated trending scores for videos', [
                    'count' => $count,
                    'duration_ms' => $duration,
                    'avg_time_per_video_ms' => $count > 0 ? round($duration / $count, 2) : 0
                ]);
            }

            return $count;
        } catch (\Exception $e) {
            \Log::error('Error updating trending scores', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Calculate recency factor for trending score
     * Enhanced with improved time decay algorithm and slight randomization
     * for more diverse feed ordering
     *
     * @param mixed $createdAt Created timestamp
     * @param bool $useCache Whether to use cache
     * @param string|null $videoId Optional video ID for consistent randomization
     * @return float Recency factor (0.1 to 1.0)
     */
    protected function calculateRecencyFactor($createdAt, bool $useCache = true, ?string $videoId = null): float
    {
        if (!$createdAt) {
            return 0.1; // Default lowest value for missing creation date
        }

        try {
            // Include video ID in cache key if provided for per-video consistency
            $cacheKey = 'recency_factor:' . md5((string) $createdAt) . ($videoId ? ':' . $videoId : '');

            // Try to get from cache first using tiered caching for better performance
            if ($useCache && $this->cacheService) {
                return $this->cacheService->getFromTieredCache($cacheKey, function () use ($createdAt, $videoId) {
                    return $this->calculateRecencyFactorInternal($createdAt, $videoId);
                }, 60 * 60, 60); // Cache for 1 hour
            }

            return $this->calculateRecencyFactorInternal($createdAt, $videoId);
        } catch (\Exception $e) {
            \Log::warning('Error calculating recency factor', [
                'error' => $e->getMessage(),
                'video_id' => $videoId ?? 'unknown'
            ]);
            return 0.1; // Default lowest value on error
        }
    }

    /**
     * Internal method to calculate recency factor with improved algorithm
     * Separated from main method to allow for better caching
     *
     * @param mixed $createdAt Created timestamp
     * @param string|null $videoId Optional video ID for consistent randomization
     * @return float Recency factor (0.1 to 1.0)
     */
    protected function calculateRecencyFactorInternal($createdAt, ?string $videoId = null): float
    {
        // Convert to Carbon instance if not already
        if (!($createdAt instanceof \Carbon\Carbon)) {
            $createdAt = \Carbon\Carbon::parse($createdAt);
        }

        $now = now();
        $ageInHours = $createdAt->diffInHours($now);
        $ageInDays = $ageInHours / 24;

        // Enhanced recency decay formula with different time periods:
        // - First 24 hours: Very high boost (1.0 - 0.8)
        // - First week: Medium boost (0.8 - 0.5)
        // - Second week: Lower boost (0.5 - 0.3)
        // - After two weeks: Minimal boost (0.3 - 0.1)
        // This creates a more natural decay curve

        if ($ageInHours <= 24) {
            // First 24 hours: linear decay from 1.0 to 0.8
            $factor = 1.0 - ($ageInHours / 24) * 0.2;
        } elseif ($ageInDays <= 7) {
            // First week: decay from 0.8 to 0.5
            $factor = 0.8 - (($ageInDays - 1) / 6) * 0.3;
        } elseif ($ageInDays <= 14) {
            // Second week: decay from 0.5 to 0.3
            $factor = 0.5 - (($ageInDays - 7) / 7) * 0.2;
        } else {
            // After two weeks: decay from 0.3 to 0.1 over the next 16 days
            $factor = max(0.1, 0.3 - (min($ageInDays - 14, 16) / 16) * 0.2);
        }

        // Add slight randomization to prevent videos with the same timestamp
        // from always appearing in the same order
        // Use video ID if available for consistent randomization per video
        if ($videoId) {
            // Create a deterministic random factor based on video ID
            // This ensures the same video always gets the same random adjustment
            $randomSeed = crc32($videoId) % 100;
            $randomFactor = 0.95 + ($randomSeed / 1000); // Range: 0.95 - 1.05 (±5%)
        } else {
            // If no video ID, use a small random factor
            $randomFactor = 0.97 + (mt_rand(0, 60) / 1000); // Range: 0.97 - 1.03 (±3%)
        }

        return round($factor * $randomFactor, 4);
    }

    /**
     * Track user interaction with a video
     * Records the interaction in VideoMetrics for efficient retrieval
     * Uses event-driven architecture with RabbitMQ
     *
     * @param string $videoId Video ID
     * @param string $userId User ID
     * @param string $interactionType Type of interaction (like, comment, view)
     * @param bool $updateScores Whether to update engagement and trending scores
     * @return bool Success status
     */
    public function trackUserInteraction(string $videoId, string $userId, string $interactionType, bool $updateScores = true): bool
    {
        try {
            // Try to publish interaction event to RabbitMQ
            try {
                VideoEvent::publishVideoInteractionEvent($videoId, $userId, $interactionType);
                Log::info('Published user interaction event to RabbitMQ', [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'type' => $interactionType
                ]);
                return true;
            } catch (Exception $e) {
                Log::warning('Failed to publish interaction event, falling back to direct DB update', [
                    'error' => $e->getMessage()
                ]);
                // Continue with direct update
            }

            // First, ensure the VideoMetrics record exists for this video
            $videoMetrics = VideoMetrics::firstOrCreate(
                ['video_id' => $videoId],
                [
                    'video_id' => $videoId,
                    'engagement_score' => 0,
                    'trending_score' => 0,
                    'user_interactions' => [],
                    'last_updated_at' => now()
                ]
            );

            // Add the user interaction to the metrics
            $result = $videoMetrics->addUserInteraction($userId, $interactionType);

            // If this is a new interaction, update the count on the video document
            if ($result && $video = Video::find($videoId)) {
                switch ($interactionType) {
                    case 'like':
                        $video->increment('likes_count');
                        break;
                    case 'comment':
                        $video->increment('comments_count');
                        break;
                    case 'view':
                        $video->increment('views_count');
                        break;
                }
            }

            // Update scores if requested
            if ($updateScores) {
                // Use queue for non-blocking updates
                $this->updateEngagementScore($videoId, true, true);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Error tracking user interaction', [
                'error' => $e->getMessage(),
                'video_id' => $videoId,
                'user_id' => $userId,
                'type' => $interactionType
            ]);
            return false;
        }
    }

    /**
     * Update engagement score for a video
     * Uses dedicated VideoScoringService and event-driven architecture with RabbitMQ
     *
     * @param string $videoId Video ID
     * @param bool $useQueue Whether to use queue for processing
     * @param bool $updateTrending Whether to also update trending score
     * @return bool Success status
     */
    public function updateEngagementScore(string $videoId, bool $useQueue = true, bool $updateTrending = true): bool
    {
        try {
            // Publish event to RabbitMQ if configured
            try {
                VideoEvent::publishEngagementScoreUpdateEvent($videoId, $updateTrending);
                \Log::info('Published engagement score update event', [
                    'video_id' => $videoId
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to publish score update event', [
                    'error' => $e->getMessage()
                ]);
                // Continue with processing - failure to publish event is not critical
            }

            // Check if the video exists before scheduling work
            $video = Video::find($videoId);
            if (!$video) {
                \Log::warning('Video not found for engagement score update', ['video_id' => $videoId]);
                return false;
            }

            // If running in console (from a job), don't create another job to avoid loops
            $isRunningInConsole = app()->runningInConsole();

            // Only dispatch job if not already in a queue process and useQueue is true
            if ($useQueue && !$isRunningInConsole) {
                UpdateVideoEngagementScore::dispatch($videoId, $updateTrending)
                    ->onQueue('low');

                \Log::info('Dispatched engagement score update job', [
                    'video_id' => $videoId,
                    'update_trending' => $updateTrending
                ]);
                return true;
            } else {
                // Either useQueue is false or we're already in a console job
                // Use VideoScoringService for direct update
                $scoringService = app(VideoScoringService::class);
                $result = $scoringService->calculateAndUpdateScores($videoId, $updateTrending);

                // Clear cache for this video
                if ($result) {
                    $this->cacheService->clearFromTieredCache("video:{$videoId}");
                }

                return $result;
            }
        } catch (\Exception $e) {
            \Log::error('Error in engagement score update process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'video_id' => $videoId
            ]);
            return false;
        }
    }

    /**
     * Track a video view with event-driven architecture
     *
     * @param string $videoId Video ID
     * @param string $userId User ID (or anonymous ID for guests)
     * @param array $metadata Additional view metadata
     * @return bool Success status
     */
    /**
     * Generate a feed of videos owned by the user
     * Includes all user's videos, including private and processing ones
     *
     * @param User $user User to generate feed for
     * @param array $options Feed options (pagination, filters, etc)
     * @return array Videos and pagination info
     */
    public function generateUserOwnVideos(User $user, array $options = [])
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('own_feed_', true);

        // Pagination and cache options
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 10;
        $useCache = $options['use_cache'] ?? true;

        try {
            // Format cache key for consistency
            $cacheKey = $this->formatCacheKey('feed', $user->id, 'own', [
                'page' => $page,
                'per_page' => $perPage
            ]);

            // Try to get from cache if allowed
            if ($useCache) {
                // Cache için parametreleri hazırla
                $ttl = (int) (self::PROFILE_FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'user_id' => $user->id,
                    'page' => $page,
                    'per_page' => $perPage,
                    'trace_id' => $traceId
                ];

                // Cache'ten kontrol et - dataCallback parametresi bir fonksiyon olmalı
                $cachedResult = $this->genericCacheOperation(
                    $cacheKey,
                    function () {
                        return [];
                    }, // Boş array döndüren callback
                    $ttl,
                    'generateUserOwnVideos',
                    $cacheContext
                );

                if (!empty($cachedResult)) {
                    return $cachedResult;
                }
            }

            // Sorgu öncesi log
            \Log::info('generateUserOwnVideos MongoDB sorgusu başlıyor', [
                'user_id' => $user->id,
                'user_id_type' => gettype($user->id),
                'page' => $page,
                'perPage' => $perPage
            ]);

            // Query for own videos (include private and all statuses for owner)
            // MongoDB'de UUID formatı sorunu için user_id'yi string olarak kullanalım
            $userId = (string) $user->id; // Kesinlikle string olarak kullan

            \Log::info('MongoDB sorgusu için ID dönüştürme', [
                'original_id' => $user->id,
                'converted_id' => $userId,
                'original_type' => gettype($user->id),
                'converted_type' => gettype($userId)
            ]);

            $query = Video::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage + 1); // Get one extra to check if there are more

            // Sorgu detaylarını log'a yaz
            try {
                $queryDetails = $query->toArray();
                \Log::info('MongoDB sorgu detayları', [
                    'query' => json_encode($queryDetails)
                ]);
            } catch (\Exception $e) {
                \Log::info('MongoDB sorgu detayları alınamadı', [
                    'error' => $e->getMessage()
                ]);
            }

            $videos = $query->get();

            // Sonuç log
            \Log::info('generateUserOwnVideos MongoDB sorgu sonucu', [
                'count' => $videos->count(),
                'first_video_id' => $videos->isNotEmpty() ? $videos->first()->id : null,
                'user_id_used' => $user->id
            ]);

            // Prepare pagination info
            $hasMore = $videos->count() > $perPage;
            if ($hasMore) {
                $videos = $videos->slice(0, $perPage);
            }

            // Toplam video sayısını hesapla - string olarak user_id kullanıyoruz
            $totalCount = Video::where('user_id', $userId)->count();

            \Log::info('Toplam video sayısı hesaplama (Own Videos)', [
                'user_id' => $userId,
                'total_count' => $totalCount
            ]);

            // Boş sonuç durumunu kontrol et
            if ($videos->isEmpty() && $totalCount > 0) {
                \Log::warning('MongoDB sorgu sonucu boş ama toplam sayı sıfır değil (Own Videos)', [
                    'user_id' => $userId,
                    'total_count' => $totalCount,
                    'page' => $page,
                    'per_page' => $perPage
                ]);

                // Direkt MongoDB ile sorgu deneyelim
                try {
                    $collection = \DB::connection('mongodb')->collection('videos');
                    $rawVideos = $collection->where('user_id', $userId)->get();
                    \Log::info('Doğrudan MongoDB sorgusu (Own Videos)', [
                        'user_id' => $userId,
                        'raw_count' => count($rawVideos),
                        'raw_first_id' => count($rawVideos) > 0 ? $rawVideos[0]['_id'] : null
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Doğrudan MongoDB sorgusu hatası (Own Videos)', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $result = [
                'videos' => $videos,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'has_more' => $hasMore
                ],
                'meta' => [
                    'total_count' => $totalCount,
                    'source' => $useCache && isset($cachedResult) ? 'cache' : 'database',
                    'timestamp' => now()->toIso8601String()
                ]
            ];

            // Save to cache for future requests
            if ($useCache) {
                // Cache için parametreleri hazırla
                $ttl = (int) (self::PROFILE_FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'user_id' => $user->id,
                    'page' => $page,
                    'per_page' => $perPage,
                    'video_count' => count($result['videos']),
                    'trace_id' => $traceId,
                    'operation' => 'put' // Put işlemi için context'e belirt
                ];

                // Cache kayıt - dataCallback olarak result'u döndüren bir fonksiyon kullan
                $finalResult = $result; // Sonucu kopyala
                $this->genericCacheOperation(
                    $cacheKey,
                    function () use ($finalResult) {
                        return $finalResult;
                    }, // Result döndüren callback
                    $ttl,
                    'generate_cache', // İşlem adı
                    $cacheContext
                );
            }

            // Enable next page pre-generation for getUserVideos
            if ($hasMore && $useCache && $page < 3) { // Only pre-generate first few pages
                $nextPage = $page + 1;
                $nextOptions = array_merge($options, ['page' => $nextPage]);

                try {
                    dispatch(function () use ($user, $nextOptions) {
                        try {
                            \Log::info('Pre-generating next page of user videos', [
                                'user_id' => $user->id,
                                'page' => $nextOptions['page'] ?? 'unknown'
                            ]);
                            app(\App\Services\VideoService::class)->generateUserOwnVideos($user, $nextOptions);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to pre-generate next page of user videos', [
                                'error' => $e->getMessage(),
                                'user_id' => $user->id
                            ]);
                        }
                    })->delay(now()->addSeconds(2));
                } catch (\Exception $e) {
                    \Log::warning('Failed to dispatch pre-generation job', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                        'page' => $nextPage
                    ]);
                }
            }

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('generateUserOwnVideos', $startTime, [
                'user_id' => $user->id,
                'page' => $page,
                'per_page' => $perPage,
                'video_count' => count($result['videos']),
                'trace_id' => $traceId,
                'from_cache' => $useCache && isset($cachedResult)
            ]);

            return $result;
        } catch (\Exception $e) {
            // Gelişmiş hata yönetimi
            try {
                return $this->handleEnhancedException($e, 'generateUserOwnVideos', [
                    'user_id' => $user->id,
                    'page' => $page,
                    'per_page' => $perPage,
                    'trace_id' => $traceId
                ]);
            } catch (\Exception $innerEx) {
                // Standart hata yönetimi - kullanıcıya boş bir feed döndür
                \Log::error('Error in generateUserOwnVideos', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id
                ]);

                return [
                    'videos' => [],
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'has_more' => false
                    ],
                    'meta' => [
                        'source' => 'error',
                        'error' => 'Failed to generate feed',
                        'timestamp' => now()->toIso8601String()
                    ]
                ];
            }
        }
    }

    /**
     * Generate a feed of videos for user profile
     * Considers public/private visibility based on profile ownership
     *
     * @param User $user Current viewer user
     * @param string $profileUserId Profile owner user ID
     * @param array $options Feed options (pagination, filters, etc)
     * @return array Videos and pagination info
     */
    public function generateProfileVideos(User $user, string $profileUserId, array $options = [])
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('profile_feed_', true);

        // Pagination and cache options
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 10;
        $bypassCache = $options['bypass_cache'] ?? false;
        $useCache = $bypassCache ? false : ($options['use_cache'] ?? true);

        try {
            // Check if viewing own profile
            $isOwnProfile = $user->id === $profileUserId;

            // Format cache key for consistency
            $cacheKey = $this->formatCacheKey('feed', $profileUserId, 'profile', [
                'page' => $page,
                'per_page' => $perPage,
                'viewer' => $isOwnProfile ? 'owner' : 'guest'
            ]);

            // Force clear cache if bypass_cache is true
            if ($bypassCache) {
                \Cache::forget($cacheKey);
                \Log::info('Profile videos cache forcefully cleared', [
                    'profile_user_id' => $profileUserId,
                    'cache_key' => $cacheKey,
                    'reason' => 'bypass_cache flag set to true'
                ]);
            }

            // Try to get from cache if allowed
            if ($useCache) {
                // Cache için parametreleri hazırla
                $ttl = (int) (self::PROFILE_FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'profile_user_id' => $profileUserId,
                    'viewer_user_id' => $user->id,
                    'page' => $page,
                    'per_page' => $perPage,
                    'trace_id' => $traceId
                ];

                // Cache'ten kontrol et - dataCallback parametresi bir fonksiyon olmalı
                try {
                    $cachedResult = $this->genericCacheOperation(
                        $cacheKey,
                        function () {
                            return [];
                        }, // Boş array döndüren callback
                        $ttl,
                        'generateProfileVideos',
                        $cacheContext
                    );

                    if (!empty($cachedResult)) {
                        \Log::info('Profile videos cache hit', [
                            'profile_user_id' => $profileUserId,
                            'cache_key' => $cacheKey,
                            'video_count' => isset($cachedResult['videos']) ? count($cachedResult['videos']) : 0
                        ]);
                        return $cachedResult;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Profile videos cache retrieval failed', [
                        'error' => $e->getMessage(),
                        'cache_key' => $cacheKey,
                        'profile_user_id' => $profileUserId
                    ]);
                    // Cache hatası durumunda devam et ve veritabanından çek
                }
            }

            // Sorgu öncesi log
            \Log::info('generateProfileVideos MongoDB sorgusu başlıyor', [
                'profile_user_id' => $profileUserId,
                'profile_user_id_type' => gettype($profileUserId),
                'viewer_user_id' => $user->id,
                'is_own_profile' => $isOwnProfile,
                'page' => $page,
                'perPage' => $perPage
            ]);

            // Base query for profile videos
            // MongoDB'de UUID formatı sorunu için user_id'yi string olarak kullanalım
            $userId = (string) $profileUserId; // Kesinlikle string olarak kullan

            \Log::info('MongoDB sorgusu için ID dönüştürme (Profile)', [
                'original_id' => $profileUserId,
                'converted_id' => $userId,
                'original_type' => gettype($profileUserId),
                'converted_type' => gettype($userId)
            ]);

            $query = Video::where('user_id', $userId);

            // Filter based on ownership
            if (!$isOwnProfile) {
                // Only show public videos to non-owners
                $query->where('is_private', false)->where('status', 'finished');
            }

            // Sorgu detaylarını log'a yaz
            try {
                // MongoDB query builder'da toArray() metodu yok, sadece filtreleri logla
                \Log::info('MongoDB sorgu detayları (Profile)', [
                    'filters' => json_encode([
                        'user_id' => $profileUserId,
                        'is_private' => !$isOwnProfile ? false : 'any',
                        'status' => !$isOwnProfile ? 'finished' : 'any'
                    ]),
                    'query_type' => get_class($query)
                ]);
            } catch (\Exception $e) {
                \Log::info('MongoDB sorgu detayları alınamadı (Profile)', [
                    'error' => $e->getMessage()
                ]);
            }

            // Get videos with pagination
            try {
                $videos = $query->orderBy('created_at', 'desc')
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage + 1) // Get one extra to check if there are more
                    ->get();

                // Log raw MongoDB response
                \Log::debug('Raw MongoDB response', [
                    'count' => $videos->count(),
                    'first_video' => $videos->isNotEmpty() ? json_encode($videos->first()) : 'empty',
                    'collection_type' => get_class($videos)
                ]);

                // Prepare pagination info
                $hasMore = $videos->count() > $perPage;
                if ($hasMore) {
                    $videos = $videos->slice(0, $perPage);
                }

                // Calculate total count for pagination
                $totalCount = Video::where('user_id', $userId)
                    ->when(!$isOwnProfile, function ($query) {
                        return $query->where('is_private', false)
                            ->where('status', 'finished');
                    })
                    ->count();

                // Sonuç formatını hazırla
                $result = [
                    'videos' => $videos->map(function ($video) {
                        return $this->serializeVideo($video);
                    })->filter(),
                    'pagination' => [
                        'page' => (int) $page,
                        'per_page' => (int) $perPage,
                        'total' => (int) $totalCount,
                        'has_more' => $hasMore === true,
                        'current_page' => (int) $page
                    ],
                    'meta' => [
                        'is_own_profile' => $isOwnProfile,
                        'source' => $useCache && isset($cachedResult) ? 'cache' : 'database',
                        'timestamp' => now()->toIso8601String()
                    ]
                ];

                // Save to cache for future requests
                if ($useCache) {
                    try {
                        $ttl = (int) (self::PROFILE_FEED_CACHE_TTL_MINUTES * 60);
                        $cacheContext = [
                            'profile_user_id' => $profileUserId,
                            'viewer_user_id' => $user->id,
                            'page' => $page,
                            'per_page' => $perPage,
                            'video_count' => count($result['videos']),
                            'trace_id' => $traceId,
                            'operation' => 'put'
                        ];

                        $this->genericCacheOperation(
                            $cacheKey,
                            function () use ($result) {
                                return $result;
                            },
                            $ttl,
                            'generateProfileVideos',
                            $cacheContext
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Failed to cache profile videos', [
                            'error' => $e->getMessage(),
                            'profile_user_id' => $profileUserId
                        ]);
                    }
                }

                return $result;
            } catch (\Exception $e) {
                \Log::error('Error fetching videos from MongoDB', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Return empty result set instead of throwing
                return [
                    'videos' => collect([]),
                    'pagination' => [
                        'page' => (int) $page,
                        'per_page' => (int) $perPage,
                        'total' => 0,
                        'has_more' => false,
                        'current_page' => (int) $page
                    ],
                    'meta' => [
                        'is_own_profile' => $isOwnProfile,
                        'source' => 'error',
                        'timestamp' => now()->toIso8601String(),
                        'error' => $e->getMessage()
                    ]
                ];
            }

            // Sonuç log
            \Log::info('generateProfileVideos MongoDB sorgu sonucu', [
                'count' => $videos->count(),
                'first_video_id' => $videos->isNotEmpty() ? $videos->first()->id : null,
                'user_id_used' => $profileUserId
            ]);

            // Prepare pagination info
            $hasMore = $videos->count() > $perPage;
            if ($hasMore) {
                $videos = $videos->slice(0, $perPage);
            }

            // For public view, do additional filtering if needed
            if (!$isOwnProfile) {
                // Filter out any videos that might be blocked/flagged
                $videos = $videos->filter(function ($video) use ($user) {
                    // Get creator
                    $creator = null;
                    try {
                        // Cross-database ilişki hatalarını önlemek için yöntem
                        $creator = User::find($video->user_id);
                        if (!$creator) {
                            return false;
                        }

                        // Creator suspended or deleted
                        if ($creator->status !== 'active') {
                            return false;
                        }
                    } catch (\Exception $e) {
                        // MongoDB/SQL cross-DB hatası olasılığına karşı embedded user datasını kullan
                        if (!empty($video->user_data)) {
                            if ($video->user_data['status'] !== 'active') {
                                return false;
                            }
                        } else {
                            // Embedded user data yoksa ve kullanıcıya erişilemiyorsa, güvenli tarafta kal
                            return false;
                        }
                    }

                    return true;
                })->values();
            }

            // Toplam video sayısını hesapla
            // MongoDB'de UUID formatı sorunu için user_id'yi string olarak kullanalım
            $totalCount = Video::where('user_id', $userId)
                ->when(!$isOwnProfile, function ($query) {
                    return $query->where('is_private', false)->where('status', 'finished');
                })
                ->count();

            \Log::info('Toplam video sayısı hesaplama (Profile)', [
                'user_id' => $userId,
                'is_own_profile' => $isOwnProfile,
                'total_count' => $totalCount
            ]);

            // Mobil uygulamanın beklediği formatta sonuç döndür
            // Sonuç dönmeden önce video sayısını kontrol et
            if ($videos->isEmpty() && $totalCount > 0) {
                \Log::warning('MongoDB sorgu sonucu boş ama toplam sayı sıfır değil', [
                    'user_id' => $userId,
                    'total_count' => $totalCount,
                    'page' => $page,
                    'per_page' => $perPage
                ]);

                // Direkt MongoDB ile sorgu deneyelim
                try {
                    $collection = \DB::connection('mongodb')->getCollection('videos');
                    $rawVideos = $collection->find(['user_id' => $userId])->toArray();
                    \Log::info('Doğrudan MongoDB sorgusu', [
                        'user_id' => $userId,
                        'raw_count' => count($rawVideos)
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Doğrudan MongoDB sorgusu hatası', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Sonuç formatını hazırla
            // MongoDB modellerini doğrudan kullan, serileştirme cache'e kaydederken yapılacak
            $result = [
                'videos' => $videos,
                'pagination' => [
                    'page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => (int) $totalCount,
                    'has_more' => $hasMore === true, // Ensure it's always a boolean
                    'current_page' => (int) $page
                ],
                'meta' => [
                    'is_own_profile' => $isOwnProfile,
                    'source' => $useCache && isset($cachedResult) ? 'cache' : 'database',
                    'timestamp' => now()->toIso8601String()
                ]
            ];

            // Save to cache for future requests
            if ($useCache) {
                // Cache için parametreleri hazırla
                $ttl = (int) (self::PROFILE_FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'profile_user_id' => $profileUserId,
                    'viewer_user_id' => $user->id,
                    'page' => $page,
                    'per_page' => $perPage,
                    'video_count' => count($result['videos']),
                    'trace_id' => $traceId,
                    'operation' => 'put' // Put işlemi için context'e belirt
                ];

                try {
                    // MongoDB modellerini serileştirme sorunlarını önlemek için
                    // video koleksiyonunu basit dizilere dönüştür
                    $serializableResult = $result;
                    if (isset($serializableResult['videos']) && $serializableResult['videos'] instanceof \Illuminate\Support\Collection) {
                        \Log::debug('Serializing video collection', [
                            'collection_count' => $serializableResult['videos']->count(),
                            'collection_type' => get_class($serializableResult['videos'])
                        ]);

                        $serializableResult['videos'] = $serializableResult['videos']->map(function ($video) {
                            try {
                                // First try to get raw MongoDB document
                                if (method_exists($video, 'getAttributes')) {
                                    $data = $video->getAttributes();
                                } else if (method_exists($video, 'toArray')) {
                                    $data = $video->toArray();
                                } else if (is_object($video)) {
                                    // For other object types, try direct cast first
                                    $data = (array) $video;
                                    if (empty($data)) {
                                        // Fallback to JSON serialization
                                        $data = json_decode(json_encode($video), true);
                                    }
                                } else {
                                    $data = $video;
                                }

                                // Log conversion details
                                \Log::debug('Video data conversion', [
                                    'original_type' => is_object($video) ? get_class($video) : gettype($video),
                                    'converted_type' => gettype($data),
                                    'has_id' => isset($data['_id']) || isset($data['id']),
                                    'keys' => is_array($data) ? array_keys($data) : 'not_array'
                                ]);

                                // Handle MongoDB ObjectId conversion
                                if (isset($data['_id'])) {
                                    if (is_array($data['_id']) && isset($data['_id']['$oid'])) {
                                        $data['id'] = $data['_id']['$oid'];
                                    } else if (is_object($data['_id']) && method_exists($data['_id'], '__toString')) {
                                        $data['id'] = (string) $data['_id'];
                                    } else {
                                        $data['id'] = (string) $data['_id'];
                                    }
                                } else if (empty($data['id'])) {
                                    if (method_exists($video, 'getKey')) {
                                        $data['id'] = (string) $video->getKey();
                                    } else if (method_exists($video, 'getId')) {
                                        $data['id'] = (string) $video->getId();
                                    }
                                }

                                // Gerekli alanların varlığını kontrol et ve eksik olanları ekle
                                if (empty($data['created_at'])) {
                                    $data['created_at'] = now()->toIso8601String();
                                } else if (is_array($data['created_at']) && isset($data['created_at']['$date'])) {
                                    // MongoDB tarih formatını düzenle
                                    try {
                                        if (is_string($data['created_at']['$date'])) {
                                            $data['created_at'] = \Carbon\Carbon::parse($data['created_at']['$date'])->toIso8601String();
                                        } else if (is_array($data['created_at']['$date'])) {
                                            // MongoDB bazen tarih verilerini iç içe dizi olarak döndürebilir
                                            \Log::warning('MongoDB tarih formatı iç içe dizi olarak geldi', [
                                                'created_at' => json_encode($data['created_at'])
                                            ]);
                                            $data['created_at'] = now()->toIso8601String();
                                        } else {
                                            $data['created_at'] = now()->toIso8601String();
                                        }
                                    } catch (\Exception $e) {
                                        // Tarih verisi düzeltilemiyorsa şu anki tarihi kullan
                                        $data['created_at'] = now()->toIso8601String();
                                        \Log::warning('MongoDB tarih formatı düzeltilemedi', [
                                            'created_at' => json_encode($data['created_at']),
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                } else if (is_string($data['created_at']) && !str_contains($data['created_at'], '+')) {
                                    // Timezone bilgisi yoksa ekle
                                    \Log::warning('Video serileştirme sırasında id alanı eksik', [
                                        'video_data' => json_encode($data)
                                    ]);
                                    return null;
                                }

                                return $data;
                            } catch (\Exception $e) {
                                \Log::error('Error during video serialization', [
                                    'error' => $e->getMessage(),
                                    'video_type' => is_object($video) ? get_class($video) : gettype($video)
                                ]);
                                // Try to extract minimal data to avoid losing the video
                                try {
                                    $minimalData = [
                                        'id' => is_object($video) && method_exists($video, 'getKey') ? $video->getKey() : (is_array($video) && isset($video['id']) ? $video['id'] : null),
                                        'title' => is_object($video) && isset($video->title) ? $video->title : (is_array($video) && isset($video['title']) ? $video['title'] : 'Unknown'),
                                        'status' => 'finished',
                                        'is_private' => false,
                                        'created_at' => now()->toIso8601String()
                                    ];

                                    if (!empty($minimalData['id'])) {
                                        \Log::info('Recovered minimal video data during serialization error', [
                                            'video_id' => $minimalData['id']
                                        ]);
                                        return $minimalData;
                                    }
                                } catch (\Exception $innerEx) {
                                    \Log::error('Failed to recover minimal video data', [
                                        'error' => $innerEx->getMessage()
                                    ]);
                                }
                                return null;
                            }
                        })->filter()->values()->toArray(); // null değerleri filtrele ve indeksleri yeniden düzenle
                    }

                    // Log serialized video count before caching
                    \Log::info('Serialized videos before caching', [
                        'video_count' => count($serializableResult['videos']),
                        'has_videos' => !empty($serializableResult['videos']),
                        'total_count' => $serializableResult['pagination']['total'] ?? 0
                    ]);

                    // Cache kayıt - dataCallback olarak serileştirilebilir result'u döndüren bir fonksiyon kullan
                    $finalResult = $serializableResult; // Sonucu kopyala
                    $this->genericCacheOperation(
                        $cacheKey,
                        function () use ($finalResult) {
                            return $finalResult;
                        }, // Result döndüren callback
                        $ttl,
                        'generate_cache', // İşlem adı
                        $cacheContext
                    );

                    \Log::info('Profile videos cache saved successfully', [
                        'profile_user_id' => $profileUserId,
                        'cache_key' => $cacheKey,
                        'video_count' => count($serializableResult['videos'])
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to cache profile videos', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'profile_user_id' => $profileUserId,
                        'cache_key' => $cacheKey
                    ]);
                }
            }

            // Asenkron olarak bir sonraki sayfayı önbellek için hazırla
            if ($hasMore && $useCache && $page < 3) { // Sadece ilk birkaç sayfa için önbellek oluştur
                $nextPage = $page + 1;
                $nextOptions = array_merge($options, ['page' => $nextPage]);

                // MongoDB bağlantısı serileştirilemediği için basit bir job kullan
                // Sadece gerekli parametreleri geçir
                $userId = $user->id;
                try {
                    dispatch(function () use ($userId, $profileUserId, $nextOptions) {
                        try {
                            // Kullanıcıyı tekrar yükle ve sonra işlemi gerçekleştir
                            $user = \App\Models\User::find($userId);
                            if ($user) {
                                \Log::info('Pre-generating next page of profile videos', [
                                    'profile_user_id' => $profileUserId,
                                    'page' => $nextOptions['page'] ?? 'unknown',
                                    'user_id' => $userId
                                ]);
                                app(\App\Services\VideoService::class)->generateProfileVideos($user, $profileUserId, $nextOptions);
                            }
                        } catch (\Exception $e) {
                            // Sessizce başarısız ol - bu bir optimizasyon, gerekli değil
                            \Log::debug('Failed to pre-generate next page of profile videos', [
                                'profile_user_id' => $profileUserId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    })->delay(now()->addSeconds(2));
                } catch (\Exception $e) {
                    \Log::warning('Failed to dispatch pre-generation job', [
                        'error' => $e->getMessage(),
                        'profile_user_id' => $profileUserId,
                        'page' => $nextPage
                    ]);
                }
            }

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('generateProfileVideos', $startTime, [
                'profile_user_id' => $profileUserId,
                'viewer_user_id' => $user->id,
                'page' => $page,
                'per_page' => $perPage,
                'video_count' => count($result['videos']),
                'trace_id' => $traceId,
                'from_cache' => $useCache && isset($cachedResult)
            ]);

            return $result;
        } catch (\Exception $e) {
            // Gelişmiş hata yönetimi
            try {
                return $this->handleEnhancedException($e, 'generateProfileVideos', [
                    'profile_user_id' => $profileUserId,
                    'viewer_user_id' => $user->id,
                    'page' => $page,
                    'per_page' => $perPage,
                    'trace_id' => $traceId
                ]);
            } catch (\Exception $innerEx) {
                // Standart hata yönetimi - kullanıcıya boş bir feed döndür
                \Log::error('Error in generateProfileVideos', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'profile_user_id' => $profileUserId,
                    'viewer_user_id' => $user->id
                ]);

                return [
                    'videos' => [],
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'has_more' => false
                    ],
                    'meta' => [
                        'is_own_profile' => $isOwnProfile ?? false,
                        'source' => 'error',
                        'error' => 'Failed to generate profile feed',
                        'timestamp' => now()->toIso8601String()
                    ]
                ];
            }
        }
    }

    /**
     * Get video by ID with cache and performance monitoring
     *
     * @param string $videoId Video ID
     * @param bool $useCache Whether to use cache
     * @param bool $includeHidden Whether to include hidden/private videos
     * @return Video|null Video or null if not found
     */
    public function getVideoById(string $videoId, bool $useCache = true, bool $includeHidden = false): ?Video
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('get_video_', true);

        try {
            // Format cache key
            $cacheKey = $this->formatCacheKey('video', $videoId, $includeHidden ? 'full' : 'filtered');

            // Try to get from cache first
            if ($useCache) {
                $ttl = (int) (self::FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'video_id' => $videoId,
                    'include_hidden' => $includeHidden,
                    'trace_id' => $traceId
                ];

                $cachedVideo = $this->genericCacheOperation(
                    $cacheKey,
                    function () {
                        return null;
                    },
                    $ttl,
                    'get_video',
                    $cacheContext
                );

                if (!empty($cachedVideo)) {
                    // Performans metriklerini kaydet
                    $this->trackPerformanceMetrics('getVideoById_cache_hit', $startTime, [
                        'video_id' => $videoId,
                        'trace_id' => $traceId
                    ]);

                    return $cachedVideo;
                }
            }

            // Not in cache or cache disabled, get from database
            $query = Video::where('_id', $videoId);

            // If not including hidden, filter out hidden/private videos
            if (!$includeHidden) {
                $query->where(function ($query) {
                    $query->where('private', false)
                        ->orWhereNull('private');
                })->whereIn('status', ['finished', 'available']);
            }

            $video = $query->first();

            // Cache the result if found
            if ($video && $useCache) {
                $ttl = (int) (self::FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'video_id' => $videoId,
                    'include_hidden' => $includeHidden,
                    'trace_id' => $traceId,
                    'operation' => 'put'
                ];

                $finalVideo = $video;
                $this->genericCacheOperation(
                    $cacheKey,
                    function () use ($finalVideo) {
                        return $finalVideo;
                    },
                    $ttl,
                    'store_video',
                    $cacheContext
                );
            }

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('getVideoById_db', $startTime, [
                'video_id' => $videoId,
                'found' => !empty($video),
                'trace_id' => $traceId
            ]);

            return $video;
        } catch (\Exception $e) {
            // Gelişmiş hata yakalama
            try {
                $this->handleEnhancedException($e, 'getVideoById', [
                    'video_id' => $videoId,
                    'trace_id' => $traceId
                ]);

                // Cross-database ilişki hatalarını tespit et
                if (strpos($e->getMessage(), 'prepare() on null') !== false) {
                    \Log::critical('Cross-database relationship error in getVideoById', [
                        'info' => 'This is likely due to MongoDB-SQL relationship issues',
                        'video_id' => $videoId,
                        'suggestion' => 'Use direct find() instead of relationships'
                    ]);
                }
            } catch (\Exception $innerEx) {
                \Log::error('Error in getVideoById', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'video_id' => $videoId
                ]);
            }

            return null;
        }
    }

    /**
     * Track video views with RabbitMQ integration and fallback
     *
     * @param string $videoId Video ID
     * @param string $userId User ID (or anonymous ID for guests)
     * @param array $metadata Additional view metadata
     * @return bool Success status
     */
    public function trackVideoView(string $videoId, string $userId, array $metadata = []): bool
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('view_', true);
        try {
            // Add to seen list for future feed filtering
            if ($userId) {
                $this->addVideoToSeenList($userId, $videoId);
            }

            // Try to publish event to RabbitMQ
            try {
                // Event-driven architecture: Publish view event
                $result = VideoEvent::publishVideoViewEvent($videoId, $userId, $metadata);

                \Log::info('Published video view event to RabbitMQ', [
                    'video_id' => $videoId,
                    'user_id' => $userId
                ]);

                return $result;
            } catch (\Exception $e) {
                \Log::warning('Failed to publish video view event to RabbitMQ, falling back to direct DB update', [
                    'error' => $e->getMessage(),
                    'video_id' => $videoId
                ]);

                // Fallback to direct database update if RabbitMQ is unavailable
                // Record the view
                VideoView::create([
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'viewed_at' => now(),
                    'metadata' => $metadata,
                    'duration_watched' => $metadata['duration_watched'] ?? 0,
                    'completed' => $metadata['completed'] ?? false
                ]);

                // Increment view count on video
                Video::where('_id', $videoId)->increment('views_count');

                // Increment play count if video was watched for sufficient time or completed
                $durationWatched = $metadata['duration_watched'] ?? 0;
                $completed = $metadata['completed'] ?? false;

                if ($completed || $durationWatched >= 10) {
                    Video::where('_id', $videoId)->increment('play_count');
                }

                // Clear cache for the video
                $this->cacheService->clearFromTieredCache("video:{$videoId}");

                // Dispatch job to update engagement score
                UpdateVideoEngagementScore::dispatch($videoId);

                // Periodically update user interests
                if (rand(1, 10) === 1) { // 10% chance to update
                    UpdateUserInterestsJob::dispatch()->delay(now()->addMinutes(5));
                }

                // MongoDB/SQL ilişkilerinde olası hataları izlemek için performans metriklerini kaydet
                $this->trackPerformanceMetrics('trackVideoView', $startTime, [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'trace_id' => $traceId,
                    'has_metadata' => !empty($metadata),
                    'duration_watched' => $durationWatched,
                    'completed' => $completed
                ]);

                return true;
            }
        } catch (\Exception $e) {
            // Cross-database ilişki hata yönetimi için özel hata işleme
            try {
                $this->handleEnhancedException($e, 'trackVideoView', [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'trace_id' => $traceId,
                    'has_metadata' => !empty($metadata)
                ]);

                // "Call to a member function prepare() on null" hatası MongoDB ve SQL modellerini
                // birlikte kullanırken çıkabilir, belirli hata mesajını kontrol edelim
                if (strpos($e->getMessage(), 'prepare() on null') !== false) {
                    \Log::critical('Cross-database relationship error detected', [
                        'info' => 'This is likely due to MongoDB-SQL relationship issues',
                        'video_id' => $videoId,
                        'user_id' => $userId,
                        'suggestion' => 'Use direct find() instead of relationships'
                    ]);
                }
            } catch (\Exception $innerEx) {
                // Yönetilemeyen hata durumunda standart loglama
                \Log::error('Error in trackVideoView', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'video_id' => $videoId,
                    'user_id' => $userId
                ]);
            }
            return false;
        }
    }

    /**
     * Check if user has already liked a video
     * Uses cached results for better performance
     *
     * @param string $videoId Video ID
     * @param string $userId User ID
     * @return bool Whether user has liked the video
     */
    public function hasUserLiked(string $videoId, string $userId): bool
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('check_like_', true);

        try {
            // Format cache key
            $cacheKey = $this->formatCacheKey('like_status', $userId, $videoId);

            // Try to get from cache first
            $ttl = (int) (self::FEED_CACHE_TTL_MINUTES * 60);
            $cacheContext = [
                'video_id' => $videoId,
                'user_id' => $userId,
                'trace_id' => $traceId
            ];

            $cachedStatus = $this->genericCacheOperation(
                $cacheKey,
                function () {
                    return null;
                },
                $ttl,
                'check_like',
                $cacheContext
            );

            if ($cachedStatus !== null) {
                // Performans metriklerini kaydet
                $this->trackPerformanceMetrics('hasUserLiked_cache', $startTime, [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'trace_id' => $traceId
                ]);

                return (bool) $cachedStatus;
            }

            // Not in cache, check database
            $exists = VideoLike::where('video_id', $videoId)
                ->where('user_id', $userId)
                ->exists();

            // Cache the result
            $finalExists = $exists;
            $this->genericCacheOperation(
                $cacheKey,
                function () use ($finalExists) {
                    return $finalExists;
                },
                $ttl,
                'store_like_status',
                array_merge($cacheContext, ['operation' => 'put'])
            );

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('hasUserLiked_db', $startTime, [
                'video_id' => $videoId,
                'user_id' => $userId,
                'liked' => $exists,
                'trace_id' => $traceId
            ]);

            return $exists;
        } catch (\Exception $e) {
            // Gelişmiş hata yakalama
            try {
                $this->handleEnhancedException($e, 'hasUserLiked', [
                    'video_id' => $videoId,
                    'user_id' => $userId,
                    'trace_id' => $traceId
                ]);

                // Cross-database ilişki hatalarını tespit et
                if (strpos($e->getMessage(), 'prepare() on null') !== false) {
                    \Log::critical('Cross-database relationship error in hasUserLiked', [
                        'info' => 'This is likely due to MongoDB-SQL relationship issues',
                        'video_id' => $videoId,
                        'user_id' => $userId
                    ]);
                }
            } catch (\Exception $innerEx) {
                \Log::error('Error in hasUserLiked', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'video_id' => $videoId,
                    'user_id' => $userId
                ]);
            }

            return false; // Hata durumunda false döndür
        }
    }

    /**
     * Get user's like count for videos
     *
     * @param string $userId User ID
     * @param bool $useCache Whether to use cache
     * @return int Number of videos liked by the user
     */
    /**
     * Get video by comment ID for efficient comment processing
     *
     * @param string $commentId Comment ID
     * @param bool $useCache Whether to use cache
     * @return Video|null Video or null if not found
     */
    public function getVideoByCommentId(string $commentId, bool $useCache = true): ?Video
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('video_by_comment_', true);

        try {
            // First find the comment
            $comment = VideoComment::find($commentId);
            if (!$comment) {
                return null;
            }

            // Now get the video
            return $this->getVideoById($comment->video_id, $useCache, true);
        } catch (\Exception $e) {
            // Gelişmiş hata yakalama
            try {
                $this->handleEnhancedException($e, 'getVideoByCommentId', [
                    'comment_id' => $commentId,
                    'trace_id' => $traceId
                ]);

                // Cross-database ilişki hatalarını tespit et
                if (strpos($e->getMessage(), 'prepare() on null') !== false) {
                    \Log::critical('Cross-database relationship error in getVideoByCommentId', [
                        'info' => 'This is likely due to MongoDB-SQL relationship issues',
                        'comment_id' => $commentId,
                        'suggestion' => 'Use direct find() instead of relationships'
                    ]);
                }
            } catch (\Exception $innerEx) {
                \Log::error('Error in getVideoByCommentId', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'comment_id' => $commentId
                ]);
            }

            return null;
        }
    }

    /**
     * Get raw videos for feed generation with optimized queries
     * Base method for various feed types that allows flexible filtering and performance tracking
     *
     * @param array $filters Query filters to apply
     * @param array $options Additional options (sorting, limit, etc)
     * @param User|null $user Current user for personalization
     * @return \Illuminate\Database\Eloquent\Collection Collection of videos
     */
    public function getRawVideosForFeed(array $filters = [], array $options = [], ?User $user = null)
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('raw_feed_', true);

        try {
            // Default options
            $defaultOptions = [
                'sort_field' => 'created_at',
                'sort_direction' => 'desc',
                'limit' => 50,
                'skip' => 0,
                'include_private' => false,
                'include_processing' => false,
                'randomize' => false,
                'feed_type' => 'custom'
            ];

            // Merge with provided options
            $options = array_merge($defaultOptions, $options);

            // Start building the query
            $query = Video::query();

            // Apply all filters
            foreach ($filters as $field => $value) {
                if (is_array($value) && isset($value['operator'])) {
                    // Handle special operators
                    switch ($value['operator']) {
                        case 'in':
                            $query->whereIn($field, $value['value']);
                            break;
                        case 'nin':
                            $query->whereNotIn($field, $value['value']);
                            break;
                        case 'gt':
                            $query->where($field, '>', $value['value']);
                            break;
                        case 'lt':
                            $query->where($field, '<', $value['value']);
                            break;
                        case 'exists':
                            if ($value['value']) {
                                $query->whereNotNull($field);
                            } else {
                                $query->whereNull($field);
                            }
                            break;
                        default:
                            $query->where($field, $value['value']);
                    }
                } else {
                    // Simple equality
                    $query->where($field, $value);
                }
            }

            // Handle privacy filters
            if (!$options['include_private']) {
                $query->where(function ($q) {
                    $q->where('private', false)
                        ->orWhereNull('private');
                });
            }

            // Handle processing status filters
            if (!$options['include_processing']) {
                $query->whereIn('status', ['finished', 'available']);
            }

            // Get blocked user IDs if user is provided
            $blockedUserIds = [];
            if ($user) {
                try {
                    // Use the User model's relationship method to get blocked users
                    if (method_exists($user, 'blocked_users')) {
                        $blockedUserIds = $user->blocked_users()->pluck('blocked_id')->toArray();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to fetch blocked users', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                }
            }

            // Filter out blocked users' videos
            if (!empty($blockedUserIds)) {
                $query->whereNotIn('user_id', $blockedUserIds);
            }

            // Apply sorting
            if (!$options['randomize']) {
                // First prioritize featured videos at the top
                // Use MongoDB raw query to sort by is_featured first
                $query->orderByRaw([
                    'is_featured' => -1, // -1 for descending (true values first)
                    'created_at' => -1   // -1 for descending (newest first)
                ]);

                // Then apply the requested sort for non-featured videos
                if ($options['sort_field'] !== 'created_at' && $options['sort_field'] !== 'is_featured') {
                    $query->orderBy($options['sort_field'], $options['sort_direction']);
                }

                // Secondary sort for stability
                if ($options['sort_field'] !== '_id') {
                    $query->orderBy('_id', 'desc');
                }
            }

            // Apply pagination
            $query->skip($options['skip'])->take($options['limit']);

            // Execute query
            $videos = $query->get();

            // Apply randomization if needed
            if ($options['randomize'] && $videos->count() > 0) {
                $videos = $videos->shuffle();
            }

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('getRawVideosForFeed', $startTime, [
                'feed_type' => $options['feed_type'],
                'filter_count' => count($filters),
                'video_count' => $videos->count(),
                'user_id' => $user ? $user->id : 'guest',
                'trace_id' => $traceId,
                'query_time_ms' => (microtime(true) - $startTime) * 1000
            ]);

            return $videos;
        } catch (\Exception $e) {
            // Gelişmiş hata yakalama
            try {
                $this->handleEnhancedException($e, 'getRawVideosForFeed', [
                    'feed_type' => $options['feed_type'] ?? 'unknown',
                    'user_id' => $user ? $user->id : 'guest',
                    'trace_id' => $traceId
                ]);
            } catch (\Exception $innerEx) {
                \Log::error('Error in getRawVideosForFeed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'feed_type' => $options['feed_type'] ?? 'unknown'
                ]);
            }

            // Return empty collection
            return collect([]);
        }
    }

    /**
     * Serialize a video model to array format
     * Also formats date fields for GraphQL compatibility
     *
     * @param mixed $video Video model or array
     * @return array|null Serialized video data or null if invalid
     */
    protected function serializeVideo($video)
    {
        try {
            // Get raw MongoDB document first
            if (method_exists($video, 'getAttributes')) {
                $data = $video->getAttributes();
            } else if (method_exists($video, 'toArray')) {
                $data = $video->toArray();
            } else if (is_object($video)) {
                // For other object types, try direct cast first
                $data = (array) $video;
                if (empty($data)) {
                    // Fallback to JSON serialization
                    $data = json_decode(json_encode($video), true);
                }
            } else if (is_array($video)) {
                $data = $video;
            } else {
                \Log::warning('Invalid video data type', [
                    'type' => gettype($video)
                ]);
                return null;
            }

            // Handle MongoDB ObjectId conversion
            if (isset($data['_id'])) {
                if (is_array($data['_id']) && isset($data['_id']['$oid'])) {
                    $data['id'] = $data['_id']['$oid'];
                } else if (is_object($data['_id']) && method_exists($data['_id'], '__toString')) {
                    $data['id'] = (string) $data['_id'];
                } else {
                    $data['id'] = (string) $data['_id'];
                }
            } else if (empty($data['id'])) {
                if (method_exists($video, 'getKey')) {
                    $data['id'] = (string) $video->getKey();
                } else if (method_exists($video, 'getId')) {
                    $data['id'] = (string) $video->getId();
                } else {
                    \Log::warning('Could not determine video ID', [
                        'data_keys' => array_keys($data)
                    ]);
                    return null;
                }
            }

            // Handle MongoDB date fields for GraphQL compatibility
            $dateFields = ['created_at', 'updated_at', 'published_at', 'deleted_at'];
            foreach ($dateFields as $dateField) {
                if (isset($data[$dateField])) {
                    try {
                        // Handle MongoDB date format
                        if (is_array($data[$dateField]) && isset($data[$dateField]['$date'])) {
                            // Convert MongoDB date to DateTime
                            $date = new \DateTime($data[$dateField]['$date']);
                            $data[$dateField] = $date->format('Y-m-d H:i:s');
                        }
                        // Handle MongoDB date objects
                        else if (is_object($data[$dateField]) && method_exists($data[$dateField], 'toDateTime')) {
                            // Convert MongoDB date object to DateTime
                            $date = $data[$dateField]->toDateTime();
                            $data[$dateField] = $date->format('Y-m-d H:i:s');
                        }
                        // Handle string dates
                        else if (is_string($data[$dateField])) {
                            // Convert string to DateTime and format it properly
                            $date = new \DateTime($data[$dateField]);
                            $data[$dateField] = $date->format('Y-m-d H:i:s');
                        }
                        // Handle DateTime objects
                        else if ($data[$dateField] instanceof \DateTime) {
                            // Format DateTime object
                            $data[$dateField] = $data[$dateField]->format('Y-m-d H:i:s');
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to parse ' . $dateField . ' date', [
                            'video_id' => $data['id'] ?? 'unknown',
                            'date' => $data[$dateField],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Ensure required fields
            $data['views_count'] = $data['views_count'] ?? 0;
            $data['likes_count'] = $data['likes_count'] ?? 0;
            $data['comments_count'] = $data['comments_count'] ?? 0;
            $data['play_count'] = $data['play_count'] ?? 0;
            $data['engagement_score'] = $data['engagement_score'] ?? 0;
            $data['trending_score'] = $data['trending_score'] ?? 0;
            $data['is_private'] = $data['is_private'] ?? false;
            $data['is_commentable'] = $data['is_commentable'] ?? true;
            $data['is_featured'] = $data['is_featured'] ?? false;
            $data['is_sport'] = $data['is_sport'] ?? true;
            $data['status'] = $data['status'] ?? 'processing';
            // Ensure tags is always an array
            if (isset($data['tags'])) {
                if (is_string($data['tags'])) {
                    // If it's a string, try to decode it as JSON
                    try {
                        $decoded = json_decode($data['tags'], true);
                        $data['tags'] = is_array($decoded) ? $decoded : [];
                    } catch (\Exception $e) {
                        $data['tags'] = [];
                    }
                } else if (!is_array($data['tags'])) {
                    $data['tags'] = [];
                }
            } else {
                $data['tags'] = [];
            }

            // Do the same for team_tags
            if (isset($data['team_tags'])) {
                if (is_string($data['team_tags'])) {
                    try {
                        $decoded = json_decode($data['team_tags'], true);
                        $data['team_tags'] = is_array($decoded) ? $decoded : [];
                    } catch (\Exception $e) {
                        $data['team_tags'] = [];
                    }
                } else if (!is_array($data['team_tags'])) {
                    $data['team_tags'] = [];
                }
            } else {
                $data['team_tags'] = [];
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('Error serializing video', [
                'error' => $e->getMessage(),
                'video_type' => is_object($video) ? get_class($video) : gettype($video)
            ]);
            return null;
        }
    }

    /**
     * Track performance metrics for operations
     *
     * @param string $operationName Name of the operation to track
     * @param float $startTime Start time of the operation from microtime(true)
     * @param array $context Additional context to store with the metric
     * @param string $status Success or error status
     * @return void
     */
    private function trackPerformanceMetrics(string $operationName, float $startTime, array $context = [], string $status = 'success'): void
    {
        try {
            // Calculate duration in milliseconds
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Determine operation type from operation name
            $operationType = explode('_', $operationName)[0];
            if (in_array($operationType, ['get', 'set', 'has', 'forget', 'remember', 'pull', 'put'])) {
                $operationType = 'cache_operation';
            }

            // Special cases based on operation name
            if (strpos($operationName, 'cache') !== false) {
                $operationType = 'cache_operation';
            } elseif (strpos($operationName, 'video') !== false) {
                $operationType = 'video_operation';
            } elseif (strpos($operationName, 'feed') !== false) {
                $operationType = 'feed_generation';
            } elseif (strpos($operationName, 'like') !== false) {
                $operationType = 'video_like';
            } elseif (strpos($operationName, 'Comment') !== false) {
                $operationType = 'comment_add';
            } elseif (strpos($operationName, 'profile') !== false) {
                $operationType = 'user_profile';
            } elseif (strpos($operationName, 'trend') !== false) {
                $operationType = 'trending_score';
            }

            // Add trace ID if not provided
            if (!isset($context['trace_id'])) {
                $context['trace_id'] = uniqid('trace_', true);
            }

            // Track the metric using our service
            $this->metricsService->trackMetric(
                $operationType,
                $operationName,
                $duration,
                $status,
                $context
            );
        } catch (\Exception $e) {
            // Just log the error but don't throw it further to avoid disrupting the main operation
            Log::error('Failed to track performance metrics', [
                'error' => $e->getMessage(),
                'operation' => $operationName,
                'context' => $context
            ]);
        }
    }

    public function getUserLikeCount(string $userId, bool $useCache = true): int
    {
        // Performans ölçümü başlat
        $startTime = microtime(true);
        $traceId = uniqid('like_count_', true);

        try {
            // Format cache key
            $cacheKey = $this->formatCacheKey('user_like_count', $userId);

            // Try to get from cache first
            if ($useCache) {
                $ttl = (int) (self::FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'user_id' => $userId,
                    'trace_id' => $traceId
                ];

                $cachedCount = $this->genericCacheOperation(
                    $cacheKey,
                    function () {
                        return null;
                    },
                    $ttl,
                    'get_like_count',
                    $cacheContext
                );

                if ($cachedCount !== null) {
                    // Performans metriklerini kaydet
                    $this->trackPerformanceMetrics('getUserLikeCount_cache', $startTime, [
                        'user_id' => $userId,
                        'trace_id' => $traceId
                    ]);

                    return (int) $cachedCount;
                }
            }

            // Not in cache or cache disabled, get from database
            $count = VideoLike::where('user_id', $userId)->count();

            // Cache the result
            if ($useCache) {
                $ttl = (int) (self::FEED_CACHE_TTL_MINUTES * 60);
                $cacheContext = [
                    'user_id' => $userId,
                    'trace_id' => $traceId,
                    'operation' => 'put'
                ];

                $finalCount = $count;
                $this->genericCacheOperation(
                    $cacheKey,
                    function () use ($finalCount) {
                        return $finalCount;
                    },
                    $ttl,
                    'store_like_count',
                    $cacheContext
                );
            }

            // Performans metriklerini kaydet
            $this->trackPerformanceMetrics('getUserLikeCount_db', $startTime, [
                'user_id' => $userId,
                'count' => $count,
                'trace_id' => $traceId
            ]);

            return $count;
        } catch (\Exception $e) {
            // Gelişmiş hata yakalama
            try {
                $this->handleEnhancedException($e, 'getUserLikeCount', [
                    'user_id' => $userId,
                    'trace_id' => $traceId
                ]);
            } catch (\Exception $innerEx) {
                \Log::error('Error in getUserLikeCount', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $userId
                ]);
            }

            return 0; // Hata durumunda 0 döndür
        }
    }
}
