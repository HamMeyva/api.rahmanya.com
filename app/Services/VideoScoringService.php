<?php

namespace App\Services;

use Exception;
use App\Models\Video;
use App\Models\VideoLike;
use App\Models\VideoView;
use App\Models\VideoComment;
use App\Models\VideoMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoScoringService
{
    /**
     * Weights for different engagement types
     */
    const LIKES_WEIGHT = 1.0;
    const COMMENTS_WEIGHT = 1.5;
    const VIEWS_WEIGHT = 0.2;
    const PLAYS_WEIGHT = 0.8;

    /**
     * Calculate and update engagement score for a video
     *
     * @param string $videoId Video ID
     * @param bool $updateTrending Whether to also update trending score
     * @return bool Success status
     */
    public function calculateAndUpdateScores(string $videoId, bool $updateTrending = true): bool
    {
        try {
            // Get the video
            $video = Video::find($videoId);
            if (!$video) {
                throw new Exception("Video not found for score calculation. Video ID: {$videoId}");
            }

            // Get counts - use existing values if available to avoid additional queries
            $likesCount = $video->likes_count ?? VideoLike::where('video_id', $videoId)->count();
            $commentsCount = $video->comments_count ?? VideoComment::where('video_id', $videoId)->count();
            $viewsCount = $video->views_count ?? VideoView::where('video_id', $videoId)->count();
            $playsCount = $video->plays_count ?? VideoMetrics::where('video_id', $videoId)->value('plays_count');

            // Calculate engagement score
            $engagementScore = ($likesCount * self::LIKES_WEIGHT) +
                ($commentsCount * self::COMMENTS_WEIGHT) +
                ($viewsCount * self::VIEWS_WEIGHT) +
                ($playsCount * self::PLAYS_WEIGHT);

            // Update the video
            $video->engagement_score = $engagementScore;

            Log::info('Updating engagement score for video $updateTrending', [
                'video_id' => $videoId,
                'video_Created_at' => $video->created_at,
                'engagement_score' => $engagementScore,
                'update_trending' => $updateTrending
            ]);
            // Calculate and update trending score if requested
            if ($updateTrending) {
                Log::info('Updating trending score for video', [
                    'video_id' => $videoId,
                    'engagement_score' => $engagementScore
                ]);
                $recencyFactor = $this->calculateRecencyFactor($video->created_at);
                Log::info('Updating trending score for video', [
                    'video_id' => $videoId,
                    'engagement_score' => $engagementScore,
                    'recency_factor' => $recencyFactor
                ]);
                // Ensure we have a minimum engagement score to avoid zero-impact in calculations
                $engagementScore = max(1, $engagementScore);
                $video->trending_score = $engagementScore * $recencyFactor;
            }

            $video->save();

            // Also update metrics collection for analytics
            $this->updateMetrics($video);

            /*Log::info('Updated video scores', [
                'video_id' => $videoId,
                'engagement_score' => $engagementScore,
                'trending_score' => $video->trending_score ?? 'not updated'
            ]);*/

            return true;
        } catch (Exception $e) {
            throw new Exception("Error calculating video scores. Error: {$e->getMessage()}");
        }
    }

    /**
     * Cache TTL for recency factor in seconds (1 hour)
     */
    const RECENCY_FACTOR_CACHE_TTL = 3600;

    /**
     * Calculate recency factor for trending score
     * Implements a sophisticated time decay mechanism
     * Uses caching to avoid repeated date calculations
     *
     * @param mixed $createdAt Created timestamp
     * @return float Recency factor (0.1 to 1.5)
     */
    public function calculateRecencyFactor($createdAt): float
    {
        // Generate a cache key based on the created_at timestamp
        // We round to the nearest hour to avoid excessive cache entries
        // while still maintaining reasonable accuracy
        $createdAtString = $createdAt->copy()->setMinute(0)->setSecond(0)->toDateTimeString();
        $cacheKey = "video_recency_factor_{$createdAtString}";

        return Cache::remember($cacheKey, self::RECENCY_FACTOR_CACHE_TTL, function () use ($createdAt) {
            $now = Carbon::now()->timezone('Europe/Istanbul');
            $ageInHours = $createdAt->diffInHours($now);
            Log::info('Updating recency factor for video calculateRecencyFactor cache', [
                'created_at' => $createdAt,
                'now' => $now,
                'age_in_hours' => $ageInHours,
            ]);

            // Enhanced decay algorithm with more granularity
            if ($ageInHours <= 2) {
                // Videos newer than 2 hours get a significant boost
                return 200;
            } else if ($ageInHours <= 4) {
                // Videos newer than 4 hours get a significant boost
                return 100;
            } else if ($ageInHours <= 6) {
                // Videos newer than 6 hours get a significant boost
                return 50;
            } else if ($ageInHours <= 8) {
                // Videos between 6-8 hours get a moderate boost
                return 20;
            } else if ($ageInHours <= 12) {
                // Videos between 8-12 hours get a moderate boost
                return 5;
            } else if ($ageInHours <= 24) {
                // Videos 1-2 days old maintain their base score
                return 0.3;
            } else if ($ageInHours <= 48) {
                // Videos 2-3 days old have slight decay
                return 0.01;
            } else if ($ageInHours <= 72) {
                // Videos 3-4 days old have slight decay
                return 0.001;
            } else if ($ageInHours <= 168) { // 7 days
                // Videos 3-7 days old have moderate decay
                return 0.0001;
            } else if ($ageInHours <= 336) { // 14 days
                // Videos 7-14 days old have significant decay
                return 0.00001;
            } else if ($ageInHours <= 720) { // 30 days
                // Videos 14-30 days old have major decay
                return 0.000001;
            } else {
                // Videos older than 30 days have minimal visibility
                return 0.0000001;
            }
        });
    }

    /**
     * Apply trending score sort with time decay to a query
     * Works with both SQL and MongoDB queries
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param bool $addSecondarySort Whether to add a secondary sort by creation date
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     */
    /**
     * Cache key for storing sort strategy to avoid recalculating it frequently
     */
    const SORT_STRATEGY_CACHE_KEY = 'video_sort_strategy';

    /**
     * Apply trending score sort with time decay to a query
     * Works with both SQL and MongoDB queries
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param bool $addSecondarySort Whether to add a secondary sort by creation date
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     */
    public function applyTrendingScoreSort($query, bool $addSecondarySort = true)
    {
        // First apply featured videos sort - always at the top
        $query->orderByDesc('is_featured');

        // Then sort by trending score - this is now consistent instead of using different strategies
        $query->orderBy('trending_score', 'desc');

        // Add secondary sort by creation date if requested
        if ($addSecondarySort) {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Determine if the query is for MongoDB
     *
     * @param mixed $query Query object
     * @return bool True if MongoDB query, false otherwise
     */
    protected function isMongoDbQuery($query): bool
    {
        try {
            // Get the class name of the query builder
            $queryClass = get_class($query);

            // Check if it contains 'Mongo' in the class name
            return stripos($queryClass, 'Mongo') !== false;
        } catch (Exception $e) {
            // If any error occurs during detection, assume it's not MongoDB to be safe
            Log::warning('Error detecting MongoDB query: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update VideoMetrics collection with current video data
     *
     * @param Video $video Video model
     * @return void
     */
    protected function updateMetrics(Video $video): void
    {
        try {
            VideoMetrics::updateOrCreate(
                ['video_id' => $video->_id],
                [
                    'likes_count' => $video->likes_count ?? 0,
                    'comments_count' => $video->comments_count ?? 0,
                    'views_count' => $video->views_count ?? 0,
                    'engagement_score' => $video->engagement_score ?? 0,
                    'trending_score' => $video->trending_score ?? 0,
                    'last_updated_at' => now()
                ]
            );

            $video->update([
                'engagement_score' => $video->engagement_score,
                'trending_score' => $video->trending_score,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to update video metrics', [
                'error' => $e->getMessage(),
                'video_id' => $video->_id
            ]);
        }
    }
}
