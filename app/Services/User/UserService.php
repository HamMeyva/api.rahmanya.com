<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    /**
     * Cache TTL constants
     */
    const USER_CACHE_TTL_MINUTES = 30;
    const USER_CACHE_PREFIX = 'user:';
    
    /**
     * Get a user by ID with caching
     *
     * @param string $userId
     * @param bool $bypassCache
     * @return User|null
     */
    public function getUserById(string $userId, bool $bypassCache = false): ?User
    {
        $cacheKey = self::USER_CACHE_PREFIX . $userId;
        
        if ($bypassCache) {
            return $this->fetchUserFromDatabase($userId);
        }
        
        return Cache::remember($cacheKey, self::USER_CACHE_TTL_MINUTES * 60, function () use ($userId) {
            return $this->fetchUserFromDatabase($userId);
        });
    }
    
    /**
     * Fetch user from database
     *
     * @param string $userId
     * @return User|null
     */
    protected function fetchUserFromDatabase(string $userId): ?User
    {
        try {
            return User::find($userId);
        } catch (\Exception $e) {
            Log::error('Error fetching user from database', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Clear user cache
     *
     * @param string $userId
     * @return void
     */
    public function clearUserCache(string $userId): void
    {
        $cacheKey = self::USER_CACHE_PREFIX . $userId;
        Cache::forget($cacheKey);
    }
    
    /**
     * Update user cache
     *
     * @param User $user
     * @return void
     */
    public function updateUserCache(User $user): void
    {
        $cacheKey = self::USER_CACHE_PREFIX . $user->id;
        Cache::put($cacheKey, $user, self::USER_CACHE_TTL_MINUTES * 60);
    }
    
    /**
     * Get filtered users with caching
     * Optimizes query #8: select * from "users" where "is_frozen" = $1 and "is_banned" = $2...
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param bool $bypassCache
     * @return array
     */
    public function getFilteredUsers(array $filters, int $page = 1, int $perPage = 10, bool $bypassCache = false): array
    {
        // Generate a unique cache key based on filters and pagination
        $cacheKey = 'filtered_users:' . md5(json_encode($filters) . "_page{$page}_perPage{$perPage}");
        
        if ($bypassCache) {
            $result = $this->fetchFilteredUsersFromDatabase($filters, $page, $perPage);
            Cache::put($cacheKey, $result, self::USER_CACHE_TTL_MINUTES * 60);
            return $result;
        }
        
        return Cache::remember($cacheKey, self::USER_CACHE_TTL_MINUTES * 60, function () use ($filters, $page, $perPage) {
            return $this->fetchFilteredUsersFromDatabase($filters, $page, $perPage);
        });
    }
    
    /**
     * Fetch filtered users from database
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function fetchFilteredUsersFromDatabase(array $filters, int $page, int $perPage): array
    {
        try {
            $query = User::query();
            
            // Apply filters with proper boolean handling for PostgreSQL
            if (isset($filters['is_frozen'])) {
                // Convert to boolean for PostgreSQL compatibility
                $isFrozen = filter_var($filters['is_frozen'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_frozen', $isFrozen ? 'true' : 'false');
            }
            
            if (isset($filters['is_banned'])) {
                // Convert to boolean for PostgreSQL compatibility
                $isBanned = filter_var($filters['is_banned'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_banned', $isBanned ? 'true' : 'false');
            }
            
            // Calculate offset
            $offset = ($page - 1) * $perPage;
            
            // Get paginated results
            $users = $query->orderBy('id')->offset($offset)->limit($perPage)->get();
            $total = $query->count();
            
            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($total / $perPage)
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching filtered users from database', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return [
                'users' => new Collection(),
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => 1
            ];
        }
    }
}
