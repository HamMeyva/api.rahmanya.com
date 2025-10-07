<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Local in-memory cache for the request lifecycle
     *
     * @var array
     */
    protected static $localCache = [];

    /**
     * Get an item from the tiered cache system
     *
     * @param string $key Cache key
     * @param \Closure $callback Callback to generate value if not in cache
     * @param int $ttlInSeconds TTL in seconds for Redis (default: 3600 = 1 hour)
     * @param int $localTtlInSeconds TTL in seconds for local cache (default: 60)
     * @return mixed
     */
    public function getFromTieredCache($key, \Closure $callback, $ttlInSeconds = 3600, $localTtlInSeconds = 60)
    {
        // Try to get from local memory cache first (fastest)
        if (array_key_exists($key, self::$localCache)) {
            $cacheItem = self::$localCache[$key];
            if ($cacheItem['expiry'] > time()) {
                return $cacheItem['value'];
            }

            // Remove expired item
            unset(self::$localCache[$key]);
        }

        // Then try Redis cache (still fast)
        if (Cache::has($key)) {
            $value = Cache::get($key);

            // Store in local cache
            self::$localCache[$key] = [
                'value' => $value,
                'expiry' => time() + $localTtlInSeconds
            ];

            return $value;
        }

        // Generate value from callback
        $value = $callback();

        // Store in Redis
        Cache::put($key, $value, $ttlInSeconds);

        // Store in local cache
        self::$localCache[$key] = [
            'value' => $value,
            'expiry' => time() + $localTtlInSeconds
        ];

        return $value;
    }

    /**
     * Put an item in the tiered cache system
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttlInSeconds TTL in seconds for Redis (default: 3600 = 1 hour)
     * @param int $localTtlInSeconds TTL in seconds for local cache (default: 60)
     * @return void
     */
    public function putInTieredCache($key, $value, $ttlInSeconds = 3600, $localTtlInSeconds = 60)
    {
        // Store in Redis
        Cache::put($key, $value, $ttlInSeconds);

        // Store in local cache
        self::$localCache[$key] = [
            'value' => $value,
            'expiry' => time() + $localTtlInSeconds
        ];
    }

    /**
     * Clear an item from the tiered cache
     *
     * @param string $key Cache key
     * @return void
     */
    public function clearFromTieredCache($key)
    {
        // Remove from Redis
        Cache::forget($key);

        // Remove from local cache
        if (array_key_exists($key, self::$localCache)) {
            unset(self::$localCache[$key]);
        }
    }

    /**
     * Clear all local cache
     *
     * @return void
     */
    public function clearLocalCache()
    {
        self::$localCache = [];
    }

    /**
     * Clear all cache keys matching a pattern
     *
     * @param string $pattern Cache key pattern
     * @return int Number of keys cleared
     */
    public function clearPatternFromTieredCache($pattern)
    {
        $count = 0;

        try {
            // Check what type of cache store we're using
            $store = Cache::getStore();

            // Redis specific implementation
            if ($store instanceof \Illuminate\Cache\RedisStore) {
                $prefix = $store->getPrefix();
                $redis = $store->getRedis();
                $keys = $redis->keys($prefix . $pattern);

                foreach ($keys as $key) {
                    // Remove prefix from key
                    $key = substr($key, strlen($prefix));
                    Cache::forget($key);
                    $count++;
                }
            }
            // Database cache store specific implementation
            else if ($store instanceof \Illuminate\Cache\DatabaseStore) {
                // For database cache, we can't easily find keys by pattern
                // So we'll just log this and not clear anything from the DB cache
                Log::warning('Pattern-based cache clearing not supported for DatabaseStore', [
                    'pattern' => $pattern
                ]);
            }
            // For any other cache store types
            else {
                Log::warning('Pattern-based cache clearing not implemented for ' . get_class($store), [
                    'pattern' => $pattern
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error clearing cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Clear from local cache (this works regardless of cache driver)
        foreach (self::$localCache as $key => $value) {
            if (fnmatch($pattern, $key)) {
                unset(self::$localCache[$key]);
                $count++;
            }
        }

        return $count;
    }
}
