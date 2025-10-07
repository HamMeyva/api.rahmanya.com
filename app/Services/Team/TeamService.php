<?php

namespace App\Services\Team;

use App\Models\Relations\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TeamService
{
    /**
     * Cache prefix for team data
     */
    const TEAM_CACHE_PREFIX = 'team:';
    
    /**
     * Cache TTL in minutes
     */
    const TEAM_CACHE_TTL_MINUTES = 30;
    
    /**
     * Get team by ID with caching
     * Optimizes queries 11 & 13: select * from "teams" where "teams"."id" in ($1, $2, $3)
     *
     * @param string $teamId
     * @param bool $bypassCache
     * @return Team|null
     */
    public function getTeamById(string $teamId, bool $bypassCache = false): ?Team
    {
        $cacheKey = self::TEAM_CACHE_PREFIX . $teamId;
        
        if ($bypassCache) {
            $team = Team::find($teamId);
            if ($team) {
                Cache::put($cacheKey, $team, self::TEAM_CACHE_TTL_MINUTES * 60);
            }
            return $team;
        }
        
        return Cache::remember($cacheKey, self::TEAM_CACHE_TTL_MINUTES * 60, function () use ($teamId) {
            try {
                return Team::find($teamId);
            } catch (\Exception $e) {
                Log::error('Error fetching team from database', [
                    'team_id' => $teamId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
    
    /**
     * Get multiple teams by IDs with caching
     * Optimizes queries 11 & 13: select * from "teams" where "teams"."id" in ($1, $2, $3)
     *
     * @param array $teamIds
     * @param bool $bypassCache
     * @return Collection
     */
    public function getTeamsByIds(array $teamIds, bool $bypassCache = false): Collection
    {
        if (empty($teamIds)) {
            return new Collection();
        }
        
        // Generate a unique cache key for this set of team IDs
        $cacheKey = self::TEAM_CACHE_PREFIX . 'multiple:' . md5(json_encode($teamIds));
        
        if ($bypassCache) {
            $teams = $this->fetchTeamsFromDatabase($teamIds);
            Cache::put($cacheKey, $teams, self::TEAM_CACHE_TTL_MINUTES * 60);
            return $teams;
        }
        
        return Cache::remember($cacheKey, self::TEAM_CACHE_TTL_MINUTES * 60, function () use ($teamIds) {
            return $this->fetchTeamsFromDatabase($teamIds);
        });
    }
    
    /**
     * Fetch teams from database
     *
     * @param array $teamIds
     * @return Collection
     */
    protected function fetchTeamsFromDatabase(array $teamIds): Collection
    {
        try {
            return Team::whereIn('id', $teamIds)->get();
        } catch (\Exception $e) {
            Log::error('Error fetching teams from database', [
                'team_ids' => $teamIds,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }
    
    /**
     * Clear team cache
     *
     * @param string $teamId
     * @return void
     */
    public function clearTeamCache(string $teamId): void
    {
        $cacheKey = self::TEAM_CACHE_PREFIX . $teamId;
        Cache::forget($cacheKey);
    }
    
    /**
     * Update team cache
     *
     * @param Team $team
     * @return void
     */
    public function updateTeamCache(Team $team): void
    {
        $cacheKey = self::TEAM_CACHE_PREFIX . $team->id;
        Cache::put($cacheKey, $team, self::TEAM_CACHE_TTL_MINUTES * 60);
    }
}
