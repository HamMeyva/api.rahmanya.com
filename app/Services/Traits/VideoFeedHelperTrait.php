<?php

namespace App\Services\Traits;

use App\Models\User;
use App\Models\Video;
use App\Models\Follow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\PreGenerateUserFeedsJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\RequestException;

/**
 * VideoFeedHelperTrait - Common feed generation utilities to be used across feed methods
 * Provides reusable methods for filtering, caching, and processing video feeds
 */
trait VideoFeedHelperTrait
{
    /**
     * Track performance metrics for feed operations
     *
     * @param string $operation Operation name (e.g., 'generateFollowingFeed')
     * @param float $startTime Start time from microtime(true)
     * @param array $metrics Additional metrics to log
     * @return void
     */
    protected function trackPerformanceMetrics(string $operation, float $startTime, array $metrics = []): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms

        $defaultMetrics = [
            'duration_ms' => $duration,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
            'timestamp' => now()->toIso8601String()
        ];

        $allMetrics = array_merge($defaultMetrics, $metrics);

        // Determine performance level for alert monitoring
        $performanceLevel = 'info';
        if ($duration > 1000) { // Over 1 second
            $performanceLevel = 'warning';
        }
        if ($duration > 3000) { // Over 3 seconds
            $performanceLevel = 'error';
        }

        // Log with appropriate level
        Log::{$performanceLevel}("Performance metric: {$operation}", $allMetrics);

        // Store to permanent storage for later analysis if the model exists
        try {
            if (class_exists('\App\Models\PerformanceMetric')) {
                \App\Models\PerformanceMetric::create([
                    'operation' => $operation,
                    'duration_ms' => $duration,
                    'metrics' => json_encode($allMetrics),
                    'level' => $performanceLevel
                ]);
            }
        } catch (\Exception $e) {
            // Fail silently - don't let performance tracking disrupt the application
            Log::warning("Failed to store performance metric", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log cache operation with detailed metrics
     *
     * @param string $cacheKey Cache key
     * @param bool $hit Whether cache hit (true) or miss (false)
     * @param array $context Additional context
     * @return void
     */
    protected function logCacheOperation(string $cacheKey, bool $hit, array $context = []): void
    {
        $operation = $hit ? 'hit' : 'miss';
        $level = $hit ? 'info' : 'debug';

        // Generate a unique trace ID for this cache operation if not already in context
        $traceId = $context['trace_id'] ?? uniqid('cache_', true);

        $metrics = [
            'cache_key' => $cacheKey,
            'operation' => $operation,
            'trace_id' => $traceId,
            'timestamp' => now()->toIso8601String()
        ];

        // Merge with additional context
        $allMetrics = array_merge($metrics, $context);

        Log::{$level}("Cache {$operation}: {$cacheKey}", $allMetrics);

        // Increment cache hit/miss counters if metrics service is available
        try {
            if (class_exists('\App\Services\MetricsService')) {
                app('\App\Services\MetricsService')->incrementCounter(
                    "cache_{$operation}_count",
                    1,
                    ['cache_key' => $cacheKey]
                );
            }
        } catch (\Exception $e) {
            // Fail silently
        }
    }

    /**
     * Enhanced exception handling for feed methods with extensive logging
     *
     * @param \Exception $exception The exception that occurred
     * @param string $operation Operation name
     * @param array $context Context data
     * @param bool $shouldRethrow Whether to rethrow the exception
     * @return array|null Default error response or null if rethrowing
     * @throws \Exception If shouldRethrow is true
     */
    protected function handleEnhancedException(\Exception $exception, string $operation, array $context = [], bool $shouldRethrow = false)
    {
        // Generate unique error ID for tracking
        $errorId = uniqid('err_', true);

        // Base error context
        $errorContext = [
            'error_id' => $errorId,
            'operation' => $operation,
            'timestamp' => now()->toIso8601String(),
            'error_type' => get_class($exception),
            'error_code' => $exception->getCode(),
            'error_message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ];

        // Merge with provided context
        $fullContext = array_merge($errorContext, $context);

        // Log with appropriate level based on exception type
        $logLevel = 'error';

        if ($exception instanceof QueryException) {
            $logLevel = 'critical';
        } elseif ($exception instanceof \Exception && strpos(get_class($exception), 'Cache') !== false) {
            $logLevel = 'warning';
        } elseif ($exception instanceof RequestException) {
            $logLevel = 'critical';
        }

        Log::{$logLevel}("Exception in {$operation}: {$exception->getMessage()}", $fullContext);

        // Notify team for critical errors if notification service exists
        if ($logLevel === 'critical' && class_exists('\App\Services\NotificationService')) {
            try {
                app('\App\Services\NotificationService')->notifyTeam(
                    "Critical error in {$operation}",
                    $fullContext
                );
            } catch (\Exception $e) {
                Log::error("Failed to send error notification", [
                    'error' => $e->getMessage(),
                    'original_error_id' => $errorId
                ]);
            }
        }

        // Report to error tracking service if available
        if (function_exists('report')) {
            report($exception);
        }

        // Rethrow if needed
        if ($shouldRethrow) {
            throw $exception;
        }

        // Return standardized error response
        return [
            'error' => true,
            'error_id' => $errorId,
            'message' => "An error occurred during {$operation}",
            'type' => 'system_error'
        ];
    }
    /**
     * Get user seen videos to prevent showing duplicates
     * 
     * @param string $userId User ID
     * @return array Array of video IDs the user has seen
     */
    protected function getUserSeenVideosForFeed(string $userId)
    {
        return $this->getUserSeenVideos($userId);
    }

    /**
     * Apply common feed filters to a video query
     * This method applies filters that are common across all feed types:
     * - Status filter (only finished/available)
     * - Blocked users filter
     * - Seen videos filter (optional)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param User|null $user Current user
     * @param bool $excludeSeen Whether to exclude seen videos
     * @param bool $excludePrivate Whether to exclude private videos
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     */
    protected function applyCommonFeedFilters($query, $user, bool $excludeSeen = true, bool $excludePrivate = true)
    {
        // Only include videos with appropriate status (finished or available)
        // Per BunnyCDN webhook integration
        $query->where(function ($q) {
            $q->where('status', 'finished')
                ->orWhere('status', 'available');
        });

        // Filter videos with invalid or non-existent user_id
        // This prevents GraphQL errors with non-nullable User fields
        $query->where(function ($q) {
            // Ensure user_id exists and is not null
            $q->whereNotNull('user_id')
                // And ensure user_data exists (for embedded user data)
                ->whereNotNull('user_data');
        });

        // Filter private videos if needed
        if ($excludePrivate) {
            $query->where('is_private', false);
        }

        // Only apply user-specific filters if user is logged in
        if ($user) {
            try {
                // Exclude videos from blocked users - handle cross-database relationship carefully
                $blockedUserIds = [];

                // Safely handle relationship methods that might exist in different versions
                if (method_exists($user, 'blockedUsers')) {
                    $blockedUserIds = $user->blockedUsers()->pluck('blocked_user_id')->toArray();
                } else if (method_exists($user, 'blocked_users')) {
                    $blockedUserIds = $user->blocked_users()->pluck('blocked_id')->toArray();
                }

                if (!empty($blockedUserIds)) {
                    $query->whereNotIn('user_id', $blockedUserIds);
                    Log::info('Excluding videos from blocked users', [
                        'count' => count($blockedUserIds)
                    ]);
                }

                // Exclude videos that user has already seen if requested
                if ($excludeSeen) {
                    $seenVideoIds = $this->getUserSeenVideos($user->id);
                    if (!empty($seenVideoIds)) {
                        $query->whereNotIn('_id', $seenVideoIds);
                        Log::info('Excluding seen videos from feed', [
                            'count' => count($seenVideoIds)
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to apply user-specific feed filters', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
            }
        }

        return $query;
    }

    /**
     * Apply randomized sorting to feed query
     * Enhanced with improved randomization algorithm to ensure more diverse feeds
     * Uses multiple factors to create a more natural and varied feed ordering
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param float $randomFactor Random factor (0.01-0.20)
     * @param string|null $userId Optional user ID for personalized randomization
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     */
    public function applyRandomizedSorting($query, float $randomFactor, ?string $userId = null)
    {
        // Ensure random factor is within reasonable bounds
        $randomFactor = max(0.01, min(0.20, $randomFactor));

        // Generate a session-consistent random seed if user ID is provided
        // This ensures the same user gets consistent randomization within a session
        // but different randomization across different sessions
        $sessionId = session()->getId() ?? uniqid();
        $randomSeed = $userId ? crc32($userId . $sessionId) : mt_rand(1, 1000000);
        $strategySelector = random_int(0, 3); //$randomSeed % 5; // 5 different strategies

        try {
            // Determine if the query is for MongoDB or SQL
            $isMongoDb = $this->isMongoDbQuery($query);

            if ($isMongoDb) {
                // MongoDB-specific implementation using simple sort parameters
                // MongoDB doesn't support orderByRaw or complex expressions
                switch ($strategySelector) {
                    case 0:
                        // Strategy 1: Recency first, then trending
                        $query->orderBy('created_at', 'desc')
                            ->orderBy('trending_score', 'desc');
                        break;

                    case 1:
                        // Strategy 2: Trending first, then recency
                        $query->orderBy('trending_score', 'desc')
                            ->orderBy('created_at', 'desc');
                        break;

                    case 2:
                        // Strategy 3: Engagement-focused
                        $query->orderBy('views_count', 'desc')
                            ->orderBy('likes_count', 'desc')
                            ->orderBy('comments_count', 'desc');
                        break;

                    case 3:
                        // Strategy 4: Mix of recency and engagement
                        $query->orderBy('created_at', 'desc')
                            ->orderBy('likes_count', 'desc');
                        break;

                    default:
                        // Strategy 5: Standard trending score
                        $query->orderBy('trending_score', 'desc');
                        break;
                }

                // Always add created_at as a secondary/final sort for consistency
                if ($strategySelector != 0 && $strategySelector != 3) {
                    $query->orderBy('created_at', 'desc');
                }
            } else {
                // SQL-specific implementation using orderByRaw
                switch ($strategySelector) {
                    case 0:
                        // Strategy 1: Recency with trending adjustment
                        $query->orderByRaw("created_at DESC, trending_score * (1 + {$randomFactor}) DESC");
                        break;

                    case 1:
                        // Strategy 2: Trending with recency adjustment
                        $query->orderByRaw("trending_score DESC, CAST(created_at as SIGNED) * (1 + {$randomFactor}) DESC");
                        break;

                    case 2:
                        // Strategy 3: Engagement-focused with slight randomness
                        $query->orderByRaw("(views_count + likes_count * 3 + comments_count * 5) * (1 + {$randomFactor}) DESC");
                        break;

                    case 3:
                        // Strategy 4: Randomized freshness weighting
                        $query->orderByRaw("CAST(created_at as SIGNED) * (1 + {$randomFactor} * 2) DESC");
                        break;

                    default:
                        // Strategy 5: Standard trending with slight randomization
                        $query->orderByRaw("trending_score * (1 + {$randomFactor}) DESC");
                        break;
                }

                // Always add created_at as secondary sort to ensure consistency
                $query->orderBy('created_at', 'desc');
            }

            Log::info('Applied enhanced randomized sorting to feed', [
                'random_factor' => $randomFactor,
                'strategy' => $strategySelector,
                'user_id' => $userId ?? 'guest',
                'database_type' => $isMongoDb ? 'mongodb' : 'sql'
            ]);
        } catch (\Exception $e) {
            // Fall back to standard sorting if advanced randomization fails
            Log::warning('Enhanced randomization failed, falling back to standard', [
                'error' => $e->getMessage(),
                'random_factor' => $randomFactor
            ]);

            // Safe fallback sorting for any database type
            try {
                // Detect MongoDB again in case the previous detection failed
                $isSimpleMongoDb = $this->isMongoDbQuery($query);

                if ($isSimpleMongoDb) {
                    // Safe MongoDB sorting
                    $query->orderBy('trending_score', 'desc')
                        ->orderBy('created_at', 'desc');
                } else {
                    // For SQL databases, we can still try a simple random factor
                    $simpleRandomFactor = $randomFactor / 2; // Reduce factor for fallback
                    $query->orderByRaw("trending_score * (1 + {$simpleRandomFactor}) DESC")
                        ->orderBy('created_at', 'desc');
                }
            } catch (\Exception $innerEx) {
                // If everything fails, use the most basic sorting
                Log::warning('Basic sorting fallback activated: ' . $innerEx->getMessage());
                $query->orderBy('trending_score', 'desc')
                    ->orderBy('created_at', 'desc');
            }
        }

        return $query;
    }

    /**
     * Determine if the query is for MongoDB or SQL database
     * 
     * @param mixed $query The query builder instance
     * @return bool True if MongoDB, false if SQL
     */
    protected function isMongoDbQuery($query)
    {
        try {
            // Check if query is a MongoDB query by checking the connection
            if (method_exists($query, 'getConnection')) {
                $connection = $query->getConnection();

                // Check connection type
                if (method_exists($connection, 'getDriverName')) {
                    $driver = $connection->getDriverName();
                    return $driver === 'mongodb' || stripos($driver, 'mongo') !== false;
                }

                // Alternative check by class name of the connection
                $connectionClass = get_class($connection);
                return stripos($connectionClass, 'Mongo') !== false;
            }

            // Check by model type (if available)
            if (method_exists($query, 'getModel')) {
                $model = $query->getModel();
                $modelClass = get_class($model);
                return stripos($modelClass, 'Mongo') !== false;
            }

            // Last resort: check class name of the query builder
            $queryClass = get_class($query);
            return stripos($queryClass, 'Mongo') !== false;
        } catch (\Exception $e) {
            // If any error occurs during detection, assume it's not MongoDB to be safe
            Log::warning('Error detecting MongoDB query: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generic cache operation with error handling, logging, and performance tracking
     *
     * @param string $cacheKey Cache key
     * @param callable $dataCallback Function to generate data if not in cache
     * @param int $ttl Cache TTL in seconds
     * @param string $operation Operation name for logging
     * @param array $context Additional context
     * @return mixed Cached data or fresh data
     */
    protected function genericCacheOperation(string $cacheKey, callable $dataCallback, int $ttl, string $operation = 'read', array $context = [])
    {
        $startTime = microtime(true);
        $traceId = uniqid('cache_op_', true);
        $context['trace_id'] = $traceId;
        $context['operation'] = $operation;

        try {
            // Check if we have a cacheService
            if (isset($this->cacheService) && method_exists($this->cacheService, 'existsInTieredCache')) {
                if ($this->cacheService->existsInTieredCache($cacheKey)) {
                    $data = $this->cacheService->getFromTieredCache($cacheKey);

                    if ($data !== null) {
                        // Cache hit
                        $this->logCacheOperation($cacheKey, true, array_merge($context, [
                            'ttl' => $ttl,
                            'data_type' => gettype($data),
                            'data_size' => is_string($data) ? strlen($data) : (is_countable($data) ? count($data) : 'unknown')
                        ]));

                        $this->trackPerformanceMetrics("cache_{$operation}_hit", $startTime, [
                            'cache_key' => $cacheKey,
                            'trace_id' => $traceId
                        ]);

                        return $data;
                    }
                }

                // Cache miss - generate fresh data
                $data = $dataCallback();

                // Store in cache if not null
                if ($data !== null) {
                    $this->cacheService->putInTieredCache($cacheKey, $data, $ttl);
                }

                $this->logCacheOperation($cacheKey, false, array_merge($context, [
                    'ttl' => $ttl,
                    'data_type' => gettype($data),
                    'data_size' => is_string($data) ? strlen($data) : (is_countable($data) ? count($data) : 'unknown')
                ]));

                $this->trackPerformanceMetrics("cache_{$operation}_miss", $startTime, [
                    'cache_key' => $cacheKey,
                    'trace_id' => $traceId
                ]);

                return $data;
            } else {
                // Fallback to Laravel's cache
                $exists = Cache::has($cacheKey);

                if ($exists) {
                    $data = Cache::get($cacheKey);

                    if ($data !== null) {
                        // Cache hit
                        $this->logCacheOperation($cacheKey, true, array_merge($context, [
                            'ttl' => $ttl,
                            'using_fallback' => true
                        ]));

                        $this->trackPerformanceMetrics("cache_{$operation}_hit_fallback", $startTime, [
                            'cache_key' => $cacheKey,
                            'trace_id' => $traceId
                        ]);

                        return $data;
                    }
                }

                // Cache miss - generate fresh data
                $data = $dataCallback();

                // Store in cache if not null
                if ($data !== null) {
                    Cache::put($cacheKey, $data, $ttl);
                }

                $this->logCacheOperation($cacheKey, false, array_merge($context, [
                    'ttl' => $ttl,
                    'using_fallback' => true
                ]));

                $this->trackPerformanceMetrics("cache_{$operation}_miss_fallback", $startTime, [
                    'cache_key' => $cacheKey,
                    'trace_id' => $traceId
                ]);

                return $data;
            }
        } catch (\Exception $e) {
            Log::error("Cache operation failed: {$operation}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cache_key' => $cacheKey,
                'trace_id' => $traceId
            ]);

            // Execute callback directly as fallback
            try {
                $data = $dataCallback();

                $this->trackPerformanceMetrics("cache_{$operation}_error_recovery", $startTime, [
                    'cache_key' => $cacheKey,
                    'trace_id' => $traceId
                ]);

                return $data;
            } catch (\Exception $innerException) {
                Log::error("Cache fallback also failed", [
                    'error' => $innerException->getMessage(),
                    'trace_id' => $traceId
                ]);

                // Re-throw with context as this is a critical failure
                throw new \Exception("Cache operation and fallback failed: {$innerException->getMessage()}", 0, $innerException);
            }
        }
    }

    /**
     * Get feed from tiered cache or create it
     * Enhanced with performance tracking and detailed logging
     * 
     * @param string $cacheKey Cache key
     * @param callable $getFeedCallback Function to generate feed if not in cache
     * @param int $cacheTtl Cache TTL in minutes
     * @return array|null Feed result or null if not in cache
     */
    protected function getFeedFromCache(string $cacheKey, callable $getFeedCallback, int $cacheTtl = 10)
    {
        try {
            $context = [
                'feed_type' => strpos($cacheKey, ':') !== false ? explode(':', $cacheKey)[1] : 'unknown',
                'cache_ttl_minutes' => $cacheTtl
            ];

            // Use the generic cache operation for consistency
            return $this->genericCacheOperation(
                $cacheKey,
                $getFeedCallback,
                $cacheTtl * 60, // Convert minutes to seconds 
                'feed_cache',
                $context
            );
        } catch (\Exception $e) {
            // Handle exception using our enhanced handler
            $this->handleEnhancedException($e, 'getFeedFromCache', [
                'cache_key' => $cacheKey,
                'cache_ttl' => $cacheTtl
            ]);

            // Return null on error but try to execute callback directly as a last resort
            try {
                Log::info('Attempting to generate feed directly after cache failure', [
                    'cache_key' => $cacheKey
                ]);
                return $getFeedCallback();
            } catch (\Exception $callbackError) {
                Log::error('Failed to generate feed directly after cache failure', [
                    'cache_key' => $cacheKey,
                    'error' => $callbackError->getMessage()
                ]);
                return null;
            }
        }
    }

    /**
     * Cache feed result and queue pre-generation of next page
     * 
     * @param string $cacheKey Cache key
     * @param array $result Feed result
     * @param int $cacheTtl Cache TTL in minutes
     * @param User|null $user User
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param int $total Total items
     * @param string $feedType Feed type (personalized, following, sport)
     * @return void
     */
    protected function cacheFeedAndQueuePreGeneration(
        string $cacheKey,
        array $result,
        int $cacheTtl,
        $user,
        int $page,
        int $perPage,
        int $total,
        string $feedType
    ) {
        // Cache the results with tiered caching
        try {
            $this->cacheService->putInTieredCache(
                $cacheKey,
                $result,
                $cacheTtl * 60, // Redis TTL in seconds
                60 // Local cache TTL in seconds
            );

            Log::info("$feedType feed completed and cached", [
                'cache_key' => $cacheKey,
                'ttl' => $cacheTtl,
                'video_count' => count($result['videos'] ?? [])
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to cache $feedType feed results", [
                'error' => $e->getMessage()
            ]);
        }

        // If this is page 1 and the user is logged in, queue pre-generation of next page
        if ($page === 1 && $total > $perPage && $user) {
            try {
                // Pre-generate next page in background to improve UX
                PreGenerateUserFeedsJob::dispatch(
                    $user instanceof \App\Models\User ? $user : $user->id,
                    2, // Next page
                    $perPage,
                    $feedType
                )->onQueue('low');

                Log::info("Queued pre-generation of next page $feedType feed", [
                    'user_id' => $user->id,
                    'next_page' => 2
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to queue $feedType feed pre-generation", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
            }
        }
    }

    /**
     * Handle feed generation errors consistently
     * 
     * @param \Exception $e Exception
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param string $userId User ID or 'guest'
     * @param string $feedType Feed type for logging
     * @return array Error result
     */
    protected function handleFeedError(\Exception $e, int $page, int $perPage, $userId, string $feedType)
    {
        Log::error("Error generating $feedType feed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $userId
        ]);

        // Return empty result set on error with error flag
        return [
            'videos' => [],
            'page' => $page,
            'per_page' => $perPage,
            'total' => 0,
            'has_more' => false,
            'current_page' => (int)$page,
            'error' => "An error occurred while generating $feedType feed"
        ];
    }

    /**
     * Get users that the target user follows and users with matching teams
     * Optimized for high-traffic scenarios with caching and improved performance
     *
     * @param User $user Target user
     * @param bool $useCache Whether to use cache
     * @param int $cacheTtl Cache TTL in minutes
     * @return array Relevant user IDs
     */
    public function getRelevantUserIds($user, bool $useCache = true, int $cacheTtl = 30)
    {
        // Create a cache key for this user
        $cacheKey = "relevant_users:{$user->id}";

        // Try to get from cache first to avoid expensive queries
        if ($useCache && Cache::has($cacheKey)) {
            $cachedResult = Cache::get($cacheKey);
            Log::info('Retrieved relevant user IDs from cache', [
                'user_id' => $user->id,
                'count' => count($cachedResult)
            ]);
            return $cachedResult;
        }

        $followedUserIds = [];
        $usersWithTeamMatch = [];

        try {
            $followedUserIds = Follow::query()
                ->where('follower_id', $user->id)
                ->where('status', 'approved')
                ->pluck('followed_id')
                ->toArray();

            Log::info('Retrieved followed user IDs', [
                'user_id' => $user->id,
                'count' => count($followedUserIds)
            ]);

            // Get user's teams efficiently
            $userTeams = [];

            // Add primary team if exists
            if ($user->primary_team_id) {
                $userTeams[] = $user->primary_team_id;
            }

            // Get teams from user_team table with a single query
            $teamIds = DB::table('user_team')
                ->where('user_id', $user->id)
                ->pluck('team_id')
                ->toArray();

            if (!empty($teamIds)) {
                $userTeams = array_unique(array_merge($userTeams, $teamIds));
            }

            // Find users with matching teams if we have any teams
            if (!empty($userTeams)) {
                // Use a more efficient query with UNION to reduce database round trips
                // This combines both primary team matches and user_team matches in one query
                $usersWithTeamMatch = DB::query()
                    ->select('id')
                    ->from(function ($query) use ($userTeams, $user) {
                        $query->select('id')
                            ->from('users')
                            ->whereIn('primary_team_id', $userTeams)
                            ->where('id', '!=', $user->id)
                            ->union(
                                DB::table('user_team')
                                    ->select('user_id as id')
                                    ->whereIn('team_id', $userTeams)
                                    ->where('user_id', '!=', $user->id)
                            );
                    }, 'combined_users')
                    ->pluck('id')
                    ->toArray();

                Log::info('Retrieved users with team matches', [
                    'user_id' => $user->id,
                    'count' => count($usersWithTeamMatch)
                ]);
            }

            // Combine all relevant user IDs
            $allRelevantUserIds = array_unique(array_merge($followedUserIds, $usersWithTeamMatch));

            // Remove duplicates
            $result = array_unique($allRelevantUserIds);

            // Store in cache for future use
            if ($useCache) {
                Cache::put($cacheKey, $result, $cacheTtl * 60);
            }

            Log::info('Generated relevant user IDs', [
                'user_id' => $user->id,
                'count' => count($result)
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error getting relevant user IDs', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty array on error
            return [];
        }
    }
}
