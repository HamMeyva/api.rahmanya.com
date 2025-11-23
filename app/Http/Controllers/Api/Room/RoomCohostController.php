<?php

namespace App\Http\Controllers\Api\Room;

use App\Http\Controllers\Controller;
use App\Events\LiveStream\CohostJoined;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RoomCohostController extends Controller
{
    /**
     * Register a cohost when joining a room
     * POST /api/room/{roomId}/register-cohost
     */
    public function registerCohost(Request $request, string $roomId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'stream_id' => 'required|string',
            'username' => 'nullable|string',
            'avatar_url' => 'nullable|string'
        ]);

        try {
            // Store in related_streams table (existing table)
            DB::table('related_streams')->updateOrInsert(
                [
                    'host_stream_id' => $roomId,
                    'cohost_stream_id' => $request->stream_id
                ],
                [
                    'host_stream_id' => $roomId,
                    'cohost_stream_id' => $request->stream_id,
                    'cohost_user_id' => $request->user_id,
                    'is_active' => true,
                    'ended_at' => null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]
            );

            // Store additional data in cache for quick access
            $cohostKey = "room:{$roomId}:cohost:{$request->user_id}";
            $cohostData = [
                'room_id' => $roomId,
                'user_id' => $request->user_id,
                'stream_id' => $request->stream_id,
                'username' => $request->username ?? null,
                'avatar_url' => $request->avatar_url ?? null,
                'joined_at' => Carbon::now()->toIso8601String(),
                'last_heartbeat' => Carbon::now()->toIso8601String()
            ];
            Cache::put($cohostKey, $cohostData, now()->addMinutes(30));

            // Log the registration
            Log::info('Cohost registered', [
                'room_id' => $roomId,
                'user_id' => $request->user_id,
                'stream_id' => $request->stream_id
            ]);

            // Trigger CohostJoined event for real-time updates
            event(new CohostJoined($roomId, [
                'id' => $request->user_id,
                'name' => $request->username ?? 'Unknown',
                'username' => $request->username,
                'avatar' => $request->avatar_url,
                'level' => null, // Can be fetched from users table if needed
                'stream_id' => $request->stream_id,
                'stream_type' => 'cohost',
                'joined_at' => Carbon::now()->toIso8601String()
            ]));

            // Get all active cohosts for response
            $activeCohosts = $this->getActiveCohostsData($roomId);

            return response()->json([
                'success' => true,
                'message' => 'Cohost registered successfully',
                'data' => [
                    'room_id' => $roomId,
                    'cohost_id' => $request->user_id,
                    'stream_id' => $request->stream_id,
                    'active_cohosts' => $activeCohosts,
                    'total_cohosts' => count($activeCohosts)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to register cohost', [
                'room_id' => $roomId,
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to register cohost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active cohosts in a room
     * GET /api/room/{roomId}/active-cohosts
     */
    public function getActiveCohosts(string $roomId): JsonResponse
    {
        try {
            $activeCohosts = $this->getActiveCohostsData($roomId);

            return response()->json([
                'success' => true,
                'data' => [
                    'room_id' => $roomId,
                    'cohosts' => $activeCohosts,
                    'total' => count($activeCohosts)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get active cohosts', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get active cohosts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cohost heartbeat
     * POST /api/room/{roomId}/heartbeat
     */
    public function heartbeat(Request $request, string $roomId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'stream_id' => 'required|string'
        ]);

        try {
            // Update heartbeat in cache
            $cohostKey = "room:{$roomId}:cohost:{$request->user_id}";
            $cohostData = Cache::get($cohostKey);

            if ($cohostData) {
                $cohostData['last_heartbeat'] = Carbon::now()->toIso8601String();
                Cache::put($cohostKey, $cohostData, now()->addMinutes(30));
            } else {
                // If not in cache, re-register
                $cohostData = [
                    'room_id' => $roomId,
                    'user_id' => $request->user_id,
                    'stream_id' => $request->stream_id,
                    'last_heartbeat' => Carbon::now()->toIso8601String()
                ];
                Cache::put($cohostKey, $cohostData, now()->addMinutes(30));
            }

            // Keep related_streams active
            DB::table('related_streams')
                ->where('host_stream_id', $roomId)
                ->where('cohost_stream_id', $request->stream_id)
                ->update([
                    'is_active' => true,
                    'updated_at' => Carbon::now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat updated'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update heartbeat', [
                'room_id' => $roomId,
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update heartbeat'
            ], 500);
        }
    }

    /**
     * Remove cohost from room (when leaving or disconnecting)
     * POST /api/room/{roomId}/remove-cohost
     */
    public function removeCohost(Request $request, string $roomId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'stream_id' => 'required|string'
        ]);

        try {
            // Mark cohost as inactive in related_streams
            DB::table('related_streams')
                ->where('host_stream_id', $roomId)
                ->where('cohost_stream_id', $request->stream_id)
                ->update([
                    'is_active' => false,
                    'ended_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

            // Remove from cache
            $cohostKey = "room:{$roomId}:cohost:{$request->user_id}";
            Cache::forget($cohostKey);

            // Get remaining active cohosts
            $activeCohosts = $this->getActiveCohostsData($roomId);

            Log::info('Cohost removed', [
                'room_id' => $roomId,
                'user_id' => $request->user_id,
                'stream_id' => $request->stream_id,
                'remaining_cohosts' => count($activeCohosts)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cohost removed successfully',
                'data' => [
                    'room_id' => $roomId,
                    'removed_user_id' => $request->user_id,
                    'active_cohosts' => $activeCohosts,
                    'total_cohosts' => count($activeCohosts)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove cohost', [
                'room_id' => $roomId,
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to remove cohost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up inactive cohosts (called periodically)
     * POST /api/room/{roomId}/cleanup-cohosts
     */
    public function cleanupInactiveCohosts(string $roomId): JsonResponse
    {
        try {
            $threshold = Carbon::now()->subMinutes(2); // Consider inactive after 2 minutes without heartbeat
            $cleanedCount = 0;

            // Get all cache keys for this room
            $pattern = "room:{$roomId}:cohost:*";
            $keys = Cache::getRedis()->keys($pattern);

            foreach ($keys as $key) {
                $cohostData = Cache::get(str_replace(config('cache.prefix') . ':', '', $key));
                if ($cohostData && isset($cohostData['last_heartbeat'])) {
                    $lastHeartbeat = Carbon::parse($cohostData['last_heartbeat']);
                    if ($lastHeartbeat->lt($threshold)) {
                        // Mark as inactive in database
                        if (isset($cohostData['stream_id'])) {
                            DB::table('related_streams')
                                ->where('host_stream_id', $roomId)
                                ->where('cohost_stream_id', $cohostData['stream_id'])
                                ->update([
                                    'is_active' => false,
                                    'ended_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ]);
                        }

                        // Remove from cache
                        Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                        $cleanedCount++;

                        Log::info('Cleaned up inactive cohost', [
                            'room_id' => $roomId,
                            'user_id' => $cohostData['user_id'] ?? 'unknown',
                            'last_heartbeat' => $cohostData['last_heartbeat']
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed',
                'data' => [
                    'room_id' => $roomId,
                    'cleaned_count' => $cleanedCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup inactive cohosts', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to cleanup cohosts'
            ], 500);
        }
    }

    /**
     * Helper: Get active cohosts data
     */
    private function getActiveCohostsData(string $roomId): array
    {
        $cohosts = [];
        $threshold = Carbon::now()->subMinutes(2);

        // Get active cohosts from database
        $dbCohosts = DB::table('related_streams')
            ->where('host_stream_id', $roomId)
            ->where('is_active', true)
            ->whereNull('ended_at')
            ->get();

        foreach ($dbCohosts as $dbCohost) {
            // Check cache for additional data
            $cohostKey = "room:{$roomId}:cohost:{$dbCohost->cohost_user_id}";
            $cacheData = Cache::get($cohostKey);

            if ($cacheData) {
                // Check if heartbeat is recent
                $lastHeartbeat = Carbon::parse($cacheData['last_heartbeat'] ?? now());
                if ($lastHeartbeat->gt($threshold)) {
                    $cohosts[] = [
                        'user_id' => $dbCohost->cohost_user_id,
                        'stream_id' => $dbCohost->cohost_stream_id,
                        'username' => $cacheData['username'] ?? null,
                        'avatar_url' => $cacheData['avatar_url'] ?? null,
                        'joined_at' => $cacheData['joined_at'] ?? Carbon::parse($dbCohost->created_at)->toIso8601String(),
                        'last_heartbeat' => $cacheData['last_heartbeat'],
                        'online_duration' => Carbon::parse($cacheData['joined_at'] ?? $dbCohost->created_at)->diffInSeconds(now())
                    ];
                }
            } else {
                // If no cache data but active in DB, include basic info
                $cohosts[] = [
                    'user_id' => $dbCohost->cohost_user_id,
                    'stream_id' => $dbCohost->cohost_stream_id,
                    'username' => null,
                    'avatar_url' => null,
                    'joined_at' => Carbon::parse($dbCohost->created_at)->toIso8601String(),
                    'last_heartbeat' => Carbon::parse($dbCohost->updated_at)->toIso8601String(),
                    'online_duration' => Carbon::parse($dbCohost->created_at)->diffInSeconds(now())
                ];
            }
        }

        return $cohosts;
    }

}