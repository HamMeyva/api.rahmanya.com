<?php

namespace App\Services\LiveStream;

use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelViewer;
use App\Models\User;
use App\Events\LiveStream\HostLeftBroadcastSplit;
use App\Events\LiveStream\StreamEnded;
use App\Events\CrossRoomMessage;
use App\Services\ZegoStreamMixerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Service to handle host leave transitions
 * Enables co-hosts to continue broadcasting independently when host leaves
 */
class HostLeaveTransitionService
{
    private ZegoStreamMixerService $mixerService;

    public function __construct(ZegoStreamMixerService $mixerService)
    {
        $this->mixerService = $mixerService;
    }

    /**
     * Handle host leaving the stream
     * Transitions co-hosts to independent broadcasting
     *
     * @param AgoraChannel $hostStream The host's stream
     * @return array Result of the transition
     */
    public function handleHostLeave(AgoraChannel $hostStream): array
    {
        DB::beginTransaction();

        try {
            Log::info('🔄 HOST LEAVE: Starting host leave transition', [
                'host_stream_id' => $hostStream->id,
                'host_user_id' => $hostStream->user_id,
                'channel_name' => $hostStream->channel_name,
            ]);

            // 1. Get all active co-hosts
            $activeCohosts = $this->getActiveCohosts($hostStream->id);

            if (empty($activeCohosts)) {
                Log::info('🔄 HOST LEAVE: No active co-hosts, ending stream normally', [
                    'host_stream_id' => $hostStream->id,
                ]);

                // No co-hosts, just end the stream normally
                return [
                    'success' => true,
                    'has_cohosts' => false,
                    'message' => 'Stream ended, no co-hosts to transition',
                ];
            }

            Log::info('🔄 HOST LEAVE: Found active co-hosts', [
                'host_stream_id' => $hostStream->id,
                'cohost_count' => count($activeCohosts),
                'cohosts' => array_column($activeCohosts, 'stream_id'),
            ]);

            // 2. Stop the mixer task (mixed stream no longer needed)
            $this->stopMixerTask($hostStream->id);

            // 3. Transition each co-host to independent broadcasting
            $transitionedCohosts = [];
            foreach ($activeCohosts as $cohost) {
                $result = $this->transitionCohostToIndependent($cohost, $hostStream);
                if ($result['success']) {
                    $transitionedCohosts[] = $result['cohost_data'];
                }
            }

            // 4. Broadcast HostLeftBroadcastSplit event to all viewers
            $this->broadcastHostLeftEvent($hostStream, $transitionedCohosts);

            // 5. Notify each co-host individually
            $this->notifyCohostsOfHostLeave($hostStream, $transitionedCohosts);

            // 6. Mark host stream as ended (but don't end co-host streams)
            // 🔥 CRITICAL: Pass cohost stream IDs so StreamEnded broadcasts to cohost channels too
            $cohostStreamIds = array_column($transitionedCohosts, 'stream_id');
            $this->markHostStreamEnded($hostStream, $cohostStreamIds);

            // 7. Update related_streams table
            $this->updateRelatedStreamsStatus($hostStream->id);

            // 8. Clean up Redis mappings
            $this->cleanupRedisMappings($hostStream->id);

            DB::commit();

            Log::info('✅ HOST LEAVE: Transition completed successfully', [
                'host_stream_id' => $hostStream->id,
                'transitioned_cohosts' => count($transitionedCohosts),
            ]);

            return [
                'success' => true,
                'has_cohosts' => true,
                'transitioned_cohosts' => $transitionedCohosts,
                'cohost_count' => count($transitionedCohosts),
                'message' => 'Host left, co-hosts transitioned to independent broadcasting',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ HOST LEAVE: Transition failed', [
                'host_stream_id' => $hostStream->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all active co-hosts for a host stream
     */
    private function getActiveCohosts(string $hostStreamId): array
    {
        $cohosts = [];

        // Get from related_streams table
        $relations = DB::table('related_streams')
            ->where('host_stream_id', $hostStreamId)
            ->where('is_active', true)
            ->whereNull('ended_at')
            ->get();

        foreach ($relations as $relation) {
            // Get the co-host's stream details
            $cohostStream = AgoraChannel::where('id', $relation->cohost_stream_id)
                ->orWhere('channel_name', $relation->cohost_stream_id)
                ->first();

            if ($cohostStream && $cohostStream->is_online) {
                $cohostUser = User::find($cohostStream->user_id);

                $cohosts[] = [
                    'stream_id' => $cohostStream->id,
                    'channel_name' => $cohostStream->channel_name,
                    'user_id' => $cohostStream->user_id,
                    'cohost_user_id' => $relation->cohost_user_id ?? $cohostStream->user_id,
                    'nickname' => $cohostUser?->nickname ?? 'Unknown',
                    'profile_photo' => $cohostUser?->profile_photo_path ?? null,
                    'thumbnail_url' => $cohostStream->thumbnail_url ?? $cohostStream->thumbnail_path ?? null,
                    'title' => $cohostStream->title,
                    'viewer_count' => $cohostStream->viewer_count ?? 0,
                ];
            }
        }

        // Also check Redis for any cohosts not in the table
        $redisCohosts = Redis::smembers("agora_channel:{$hostStreamId}:active_cohosts");
        foreach ($redisCohosts as $cohostUserId) {
            // Check if already added
            $alreadyAdded = collect($cohosts)->contains('cohost_user_id', $cohostUserId);
            if (!$alreadyAdded) {
                $cohostStream = AgoraChannel::where('user_id', $cohostUserId)
                    ->where('is_online', true)
                    ->where('is_cohost_stream', true)
                    ->first();

                if ($cohostStream) {
                    $cohostUser = User::find($cohostUserId);
                    $cohosts[] = [
                        'stream_id' => $cohostStream->id,
                        'channel_name' => $cohostStream->channel_name,
                        'user_id' => $cohostStream->user_id,
                        'cohost_user_id' => $cohostUserId,
                        'nickname' => $cohostUser?->nickname ?? 'Unknown',
                        'profile_photo' => $cohostUser?->profile_photo_path ?? null,
                        'thumbnail_url' => $cohostStream->thumbnail_url ?? $cohostStream->thumbnail_path ?? null,
                        'title' => $cohostStream->title,
                        'viewer_count' => $cohostStream->viewer_count ?? 0,
                    ];
                }
            }
        }

        return $cohosts;
    }

    /**
     * Transition a co-host to independent broadcasting
     */
    private function transitionCohostToIndependent(array $cohost, AgoraChannel $hostStream): array
    {
        try {
            $cohostStream = AgoraChannel::find($cohost['stream_id']);

            if (!$cohostStream) {
                return ['success' => false, 'error' => 'Co-host stream not found'];
            }

            Log::info('🔄 Transitioning co-host to independent', [
                'cohost_stream_id' => $cohostStream->id,
                'cohost_user_id' => $cohost['user_id'],
            ]);

            // Update co-host stream to be independent
            $cohostStream->is_cohost_stream = false;
            $cohostStream->is_cohost = false;
            $cohostStream->parent_stream_id = null;
            $cohostStream->parent_channel_id = null;
            $cohostStream->host_stream_id = null;
            $cohostStream->status_id = AgoraChannel::STATUS_LIVE;

            // Keep the stream as a standalone live stream
            // Use its own channel as the shared video room
            $cohostStream->shared_video_room_id = $cohostStream->channel_name;

            $cohostStream->save();

            // Update the viewer role if they were marked as co-host
            AgoraChannelViewer::where('agora_channel_id', $cohostStream->id)
                ->where('user_id', $cohost['user_id'])
                ->where('role_id', AgoraChannelViewer::ROLE_HOST)
                ->update([
                    'last_activity_at' => now(),
                ]);

            // Set heartbeat for the independent stream
            Cache::put("stream_heartbeat_{$cohostStream->id}", now(), 120);

            return [
                'success' => true,
                'cohost_data' => [
                    'stream_id' => $cohostStream->id,
                    'channel_name' => $cohostStream->channel_name,
                    'user_id' => $cohost['user_id'],
                    'nickname' => $cohost['nickname'],
                    'profile_photo' => $cohost['profile_photo'],
                    'thumbnail_url' => $cohost['thumbnail_url'],
                    'title' => $cohostStream->title ?? "{$cohost['nickname']}'s Stream",
                    'viewer_count' => $cohostStream->viewer_count ?? 0,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to transition co-host', [
                'cohost_stream_id' => $cohost['stream_id'],
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Broadcast the HostLeftBroadcastSplit event
     */
    private function broadcastHostLeftEvent(AgoraChannel $hostStream, array $transitionedCohosts): void
    {
        try {
            Event::dispatch(new HostLeftBroadcastSplit(
                $hostStream,
                $transitionedCohosts,
                'Host yayından ayrıldı, co-hostlar bağımsız yayına geçiyor...'
            ));

            Log::info('📢 HOST LEAVE: Broadcast HostLeftBroadcastSplit event', [
                'host_stream_id' => $hostStream->id,
                'cohost_count' => count($transitionedCohosts),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast HostLeftBroadcastSplit', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify each co-host individually about host leaving
     */
    private function notifyCohostsOfHostLeave(AgoraChannel $hostStream, array $transitionedCohosts): void
    {
        foreach ($transitionedCohosts as $cohost) {
            try {
                // Send direct notification to co-host's room
                broadcast(new CrossRoomMessage([
                    'room_id' => $cohost['stream_id'],
                    'source_stream_id' => 'system',
                    'message' => [
                        'type' => 'host_left',
                        'host_stream_id' => $hostStream->id,
                        'host_user_id' => $hostStream->user_id,
                        'your_stream_id' => $cohost['stream_id'],
                        'your_channel_name' => $cohost['channel_name'],
                        'transition_type' => 'independent',
                        'message' => 'Host yayından ayrıldı. Artık bağımsız yayın yapıyorsunuz.',
                        'other_cohosts' => array_filter($transitionedCohosts, function ($c) use ($cohost) {
                            return $c['stream_id'] !== $cohost['stream_id'];
                        }),
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]))->toOthers();

                Log::info('📢 HOST LEAVE: Notified co-host', [
                    'cohost_stream_id' => $cohost['stream_id'],
                    'cohost_user_id' => $cohost['user_id'],
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to notify co-host of host leave', [
                    'cohost_stream_id' => $cohost['stream_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Mark the host stream as ended
     * @param AgoraChannel $hostStream The host stream to mark as ended
     * @param array $cohostStreamIds Optional cohost stream IDs to broadcast to
     */
    private function markHostStreamEnded(AgoraChannel $hostStream, array $cohostStreamIds = []): void
    {
        $hostStream->is_online = false;
        $hostStream->status_id = AgoraChannel::STATUS_ENDED;
        $hostStream->ended_at = now();

        if ($hostStream->started_at) {
            $hostStream->duration = (int) $hostStream->started_at->diffInSeconds(now());
        }

        $hostStream->save();

        // 🔥 CRITICAL: Dispatch StreamEnded event with cohost channel IDs
        // This ensures viewers watching cohost streams also receive the event
        Log::info('📢 HOST LEAVE: Dispatching StreamEnded event', [
            'host_stream_id' => $hostStream->id,
            'cohost_stream_ids' => $cohostStreamIds,
        ]);

        Event::dispatch(new StreamEnded($hostStream, $cohostStreamIds));

        Log::info('📢 HOST LEAVE: Host stream marked as ended', [
            'host_stream_id' => $hostStream->id,
            'broadcast_to_cohost_channels' => count($cohostStreamIds),
        ]);
    }

    /**
     * Update related_streams table to reflect host leaving
     */
    private function updateRelatedStreamsStatus(string $hostStreamId): void
    {
        DB::table('related_streams')
            ->where('host_stream_id', $hostStreamId)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info('📢 HOST LEAVE: Updated related_streams status', [
            'host_stream_id' => $hostStreamId,
        ]);
    }

    /**
     * Clean up Redis mappings
     */
    private function cleanupRedisMappings(string $hostStreamId): void
    {
        try {
            // Clean room mappings
            Redis::del("room_mapping:{$hostStreamId}");

            // Clean cohost sets
            Redis::del("agora_channel:{$hostStreamId}:cohosts");
            Redis::del("agora_channel:{$hostStreamId}:active_cohosts");

            // Clean heartbeat cache
            Cache::forget("stream_heartbeat_{$hostStreamId}");
            Cache::forget("stream_cohosts_{$hostStreamId}");

            Log::info('📢 HOST LEAVE: Cleaned up Redis mappings', [
                'host_stream_id' => $hostStreamId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clean up Redis mappings', [
                'host_stream_id' => $hostStreamId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Stop the mixer task
     */
    private function stopMixerTask(string $hostStreamId): void
    {
        try {
            $existingMixer = $this->mixerService->getActiveMixerForStream($hostStreamId);

            if ($existingMixer && isset($existingMixer['task_id'])) {
                $this->mixerService->stopMixerTask($existingMixer['task_id']);
                Log::info('📢 HOST LEAVE: Stopped mixer task', [
                    'host_stream_id' => $hostStreamId,
                    'task_id' => $existingMixer['task_id'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to stop mixer task', [
                'host_stream_id' => $hostStreamId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if a stream has active co-hosts
     */
    public function hasActiveCohosts(string $streamId): bool
    {
        $count = DB::table('related_streams')
            ->where('host_stream_id', $streamId)
            ->where('is_active', true)
            ->whereNull('ended_at')
            ->count();

        return $count > 0;
    }

    /**
     * Get active co-host count for a stream
     */
    public function getActiveCohostCount(string $streamId): int
    {
        return DB::table('related_streams')
            ->where('host_stream_id', $streamId)
            ->where('is_active', true)
            ->whereNull('ended_at')
            ->count();
    }
}
