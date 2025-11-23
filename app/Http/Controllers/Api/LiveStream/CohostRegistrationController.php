<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Services\ZegoStreamMixerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\MixedStreamUpdate;
use Illuminate\Support\Facades\Redis;
use App\Events\CrossRoomMessage;

class CohostRegistrationController extends Controller
{
    private ZegoStreamMixerService $mixerService;

    public function __construct(ZegoStreamMixerService $mixerService)
    {
        $this->mixerService = $mixerService;
    }

    /**
     * Register a co-host when they join (called from Flutter app)
     * This endpoint ensures proper mixer update when co-hosts join
     */
    public function registerCohostJoin(Request $request): JsonResponse
    {
        $request->validate([
            'host_stream_id' => 'required|string',
            'cohost_stream_id' => 'required|string',
            'cohost_user_id' => 'required|string'
        ]);

        DB::beginTransaction();

        try {
            Log::info('Registering cohost join', [
                'host_stream' => $request->host_stream_id,
                'cohost_stream' => $request->cohost_stream_id,
                'cohost_user' => $request->cohost_user_id
            ]);

            // Store the relationship
            DB::table('related_streams')->updateOrInsert(
                ['cohost_stream_id' => $request->cohost_stream_id],
                [
                    'host_stream_id' => $request->host_stream_id,
                    'cohost_user_id' => $request->cohost_user_id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            // Store room mapping in Redis for fast lookups
            $cohostRoomId = 'room_' . $request->cohost_stream_id;
            Redis::hset(
                'room_mapping:' . $request->host_stream_id,
                $request->cohost_stream_id,
                $cohostRoomId
            );

            // Set expiration (24 hours)
            Redis::expire('room_mapping:' . $request->host_stream_id, 86400);

            // Get all active participants for the mixer
            $participants = $this->getAllActiveParticipants($request->host_stream_id);

            // Check if mixer exists
            $existingMixer = $this->mixerService->getActiveMixerForStream($request->host_stream_id);

            if ($existingMixer) {
                // Update existing mixer with all participants
                $result = $this->mixerService->updateMixerTask($existingMixer['task_id'], $participants);
                $mixedStreamId = $existingMixer['mixed_stream_id'];
                $taskId = $existingMixer['task_id'];
            } else {
                // Create new mixer
                $result = $this->mixerService->startMixerTask($participants);
                $mixedStreamId = $result['mixed_stream_id'] ?? null;
                $taskId = $result['task_id'] ?? null;
            }

            if (!$result['success']) {
                throw new \Exception('Failed to update mixer: ' . ($result['error'] ?? 'Unknown error'));
            }

            // Broadcast update to all participants
            $this->broadcastMixerUpdate($request->host_stream_id, $mixedStreamId, $participants);

            // Also broadcast to each cohost room
            foreach ($participants as $participant) {
                if ($participant['stream_id'] !== $request->host_stream_id) {
                    $this->broadcastMixerUpdate($participant['stream_id'], $mixedStreamId, $participants);
                }
            }

            // Broadcast cohost joined event to host room
            broadcast(new CrossRoomMessage([
                'room_id' => $request->host_stream_id,
                'source_stream_id' => 'system',
                'message' => [
                    'type' => 'cohost_joined',
                    'cohost_stream_id' => $request->cohost_stream_id,
                    'cohost_user_id' => $request->cohost_user_id,
                    'cohost_room_id' => 'room_' . $request->cohost_stream_id
                ],
                'timestamp' => now()->toIso8601String()
            ]))->toOthers();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Co-host registered successfully',
                'data' => [
                    'mixed_stream_id' => $mixedStreamId,
                    'task_id' => $taskId,
                    'participants' => $participants,
                    'participant_count' => count($participants)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to register cohost', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active participants including host and all cohosts
     */
    private function getAllActiveParticipants(string $hostStreamId): array
    {
        $participants = [];

        // Add host
        $hostChannel = AgoraChannel::where('id', $hostStreamId)
            ->orWhere('channel_name', $hostStreamId)
            ->first();

        if ($hostChannel) {
            $participants[] = [
                'stream_id' => $hostChannel->channel_name ?? $hostChannel->id,
                'user_id' => $hostChannel->user_id,
                'chat_room_id' => 'chat_' . ($hostChannel->channel_name ?? $hostChannel->id)
            ];
        }

        // Add all active cohosts
        $cohosts = DB::table('related_streams')
            ->where('host_stream_id', $hostStreamId)
            ->where('is_active', true)
            ->whereNull('ended_at')
            ->get();

        foreach ($cohosts as $cohost) {
            // Verify cohost stream is still active
            $cohostChannel = AgoraChannel::where('id', $cohost->cohost_stream_id)
                ->orWhere('channel_name', $cohost->cohost_stream_id)
                ->first();

            if ($cohostChannel && $cohostChannel->is_online) {
                $participants[] = [
                    'stream_id' => $cohostChannel->channel_name ?? $cohostChannel->id,
                    'user_id' => $cohost->cohost_user_id ?? $cohostChannel->user_id,
                    'chat_room_id' => 'chat_' . ($cohostChannel->channel_name ?? $cohostChannel->id)
                ];
            }
        }

        Log::info('Collected participants for mixer', [
            'host_stream' => $hostStreamId,
            'total_participants' => count($participants),
            'participants' => $participants
        ]);

        return $participants;
    }

    /**
     * Broadcast mixer update to a room
     */
    private function broadcastMixerUpdate(string $roomId, ?string $mixedStreamId, array $participants): void
    {
        try {
            broadcast(new MixedStreamUpdate([
                'room_id' => $roomId,
                'mixed_stream_id' => $mixedStreamId,
                'participants' => $participants,
                'layout_type' => $this->getLayoutType(count($participants)),
                'participant_count' => count($participants),
                'timestamp' => now()->toIso8601String()
            ]))->toOthers();

            Log::info('Broadcasted mixer update', [
                'room_id' => $roomId,
                'participant_count' => count($participants)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast mixer update', [
                'error' => $e->getMessage(),
                'room_id' => $roomId
            ]);
        }
    }

    /**
     * Get layout type based on participant count
     */
    private function getLayoutType(int $count): string
    {
        return match($count) {
            1 => 'single',
            2 => 'side_by_side',
            3 => 'three_way',
            4 => 'grid_2x2',
            default => 'grid'
        };
    }

    /**
     * Notify when a cohost leaves
     */
    public function notifyCohostLeave(Request $request): JsonResponse
    {
        $request->validate([
            'cohost_stream_id' => 'required|string'
        ]);

        try {
            // Get host stream ID first
            $relation = DB::table('related_streams')
                ->where('cohost_stream_id', $request->cohost_stream_id)
                ->first();

            // Mark cohost as inactive
            DB::table('related_streams')
                ->where('cohost_stream_id', $request->cohost_stream_id)
                ->update([
                    'is_active' => false,
                    'ended_at' => now(),
                    'updated_at' => now()
                ]);

            // Clean up Redis room mapping
            if ($relation && $relation->host_stream_id) {
                Redis::hdel('room_mapping:' . $relation->host_stream_id, $request->cohost_stream_id);
            }

            if ($relation && $relation->host_stream_id) {
                // Get remaining participants
                $participants = $this->getAllActiveParticipants($relation->host_stream_id);

                // Update mixer
                $existingMixer = $this->mixerService->getActiveMixerForStream($relation->host_stream_id);

                if ($existingMixer) {
                    if (count($participants) > 1) {
                        // Update mixer with remaining participants
                        $this->mixerService->updateMixerTask($existingMixer['task_id'], $participants);
                    } else {
                        // Stop mixer if only host remains
                        $this->mixerService->stopMixerTask($existingMixer['task_id']);
                    }

                    // Broadcast update
                    $this->broadcastMixerUpdate(
                        $relation->host_stream_id,
                        count($participants) > 1 ? $existingMixer['mixed_stream_id'] : null,
                        $participants
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Co-host leave registered'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to register cohost leave', [
                'error' => $e->getMessage(),
                'cohost_stream_id' => $request->cohost_stream_id
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Relay messages between host and cohost rooms
     */
    public function relayMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|array',
            'source_stream_id' => 'required|string',
            'target_stream_ids' => 'sometimes|array',
            'target_stream_ids.*' => 'string',
            'broadcast_to_all' => 'sometimes|boolean'
        ]);

        try {
            $sourceStreamId = $request->source_stream_id;
            $message = $request->message;
            $targetStreamIds = $request->target_stream_ids ?? [];

            // If broadcast_to_all is true, get all related streams
            if ($request->broadcast_to_all) {
                // Check if source is a host
                $isHost = DB::table('related_streams')
                    ->where('host_stream_id', $sourceStreamId)
                    ->where('is_active', true)
                    ->exists();

                if ($isHost) {
                    // Source is host, get all cohosts
                    $cohosts = DB::table('related_streams')
                        ->where('host_stream_id', $sourceStreamId)
                        ->where('is_active', true)
                        ->pluck('cohost_stream_id')
                        ->toArray();
                    $targetStreamIds = $cohosts;
                } else {
                    // Source is cohost, get host and other cohosts
                    $relation = DB::table('related_streams')
                        ->where('cohost_stream_id', $sourceStreamId)
                        ->where('is_active', true)
                        ->first();

                    if ($relation) {
                        // Get host
                        $targetStreamIds[] = $relation->host_stream_id;

                        // Get other cohosts
                        $otherCohosts = DB::table('related_streams')
                            ->where('host_stream_id', $relation->host_stream_id)
                            ->where('cohost_stream_id', '!=', $sourceStreamId)
                            ->where('is_active', true)
                            ->pluck('cohost_stream_id')
                            ->toArray();

                        $targetStreamIds = array_merge($targetStreamIds, $otherCohosts);
                    }
                }
            }

            // Remove duplicates
            $targetStreamIds = array_unique($targetStreamIds);

            Log::info('Relaying message', [
                'source' => $sourceStreamId,
                'targets' => $targetStreamIds,
                'message_type' => $message['type'] ?? 'unknown'
            ]);

            // Broadcast to each target stream's room
            foreach ($targetStreamIds as $targetStreamId) {
                try {
                    // Create room-specific event
                    broadcast(new CrossRoomMessage([
                        'room_id' => $targetStreamId,
                        'source_stream_id' => $sourceStreamId,
                        'message' => $message,
                        'timestamp' => now()->toIso8601String()
                    ]))->toOthers();

                    // Also broadcast to chat room if it exists
                    $chatRoomId = 'chat_' . $targetStreamId;
                    broadcast(new CrossRoomMessage([
                        'room_id' => $chatRoomId,
                        'source_stream_id' => $sourceStreamId,
                        'message' => $message,
                        'timestamp' => now()->toIso8601String()
                    ]))->toOthers();

                } catch (\Exception $e) {
                    Log::warning('Failed to relay message to stream', [
                        'target_stream' => $targetStreamId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Message relayed successfully',
                'relayed_to' => count($targetStreamIds) . ' streams'
            ]);

        } catch (\Exception $e) {
            Log::error('Message relay failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}