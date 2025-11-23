<?php

namespace App\Http\Controllers;

use App\Services\ZegoStreamMixerService;
use App\Models\Agora\AgoraChannel;
use App\Events\MixedStreamUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZegoMixerController extends Controller
{
    private ZegoStreamMixerService $mixerService;

    public function __construct(ZegoStreamMixerService $mixerService)
    {
        $this->mixerService = $mixerService;
    }

    /**
     * Co-host katıldığında mixer'ı güncelle
     */
    public function notifyCohostJoined(Request $request)
    {
        try {
            $validated = $request->validate([
                'original_stream_id' => 'required|string',
                'cohost_stream_id' => 'required|string',
                'cohost_user_id' => 'required|string',
            ]);

            Log::info('Notify cohost joined', [
                'original_stream' => $validated['original_stream_id'],
                'cohost_stream' => $validated['cohost_stream_id'],
                'cohost_user' => $validated['cohost_user_id']
            ]);

            // Get all active participants from the original stream
            $allParticipants = $this->getAllStreamParticipants($validated['original_stream_id']);

            // Add the new cohost to participants
            $allParticipants[] = [
                'stream_id' => $validated['cohost_stream_id'],
                'user_id' => $validated['cohost_user_id'],
                'chat_room_id' => 'chat_' . $validated['cohost_stream_id']
            ];

            // Check for existing mixer
            $existingMixer = $this->mixerService->getActiveMixerForStream($validated['original_stream_id']);

            if ($existingMixer) {
                // Update existing mixer with all participants
                Log::info('Updating existing mixer', [
                    'task_id' => $existingMixer['task_id'],
                    'participant_count' => count($allParticipants)
                ]);

                $result = $this->mixerService->updateMixerTask($existingMixer['task_id'], $allParticipants);

                // Broadcast update to all participants
                $this->broadcastMixerUpdate($validated['original_stream_id'], $existingMixer['mixed_stream_id'], $allParticipants);

                return response()->json([
                    'success' => true,
                    'mixed_stream_id' => $existingMixer['mixed_stream_id'],
                    'task_id' => $existingMixer['task_id'],
                    'participants' => $allParticipants,
                    'layout_type' => $this->getLayoutType(count($allParticipants))
                ]);
            } else {
                // Create new mixer with all participants
                Log::info('Creating new mixer', [
                    'participant_count' => count($allParticipants)
                ]);

                $result = $this->mixerService->startMixerTask($allParticipants);

                if ($result['success']) {
                    // Store mixer info for all streams
                    $this->storeMixerInfo($result, $allParticipants);

                    // Broadcast to all participants
                    $this->broadcastMixerUpdate($validated['original_stream_id'], $result['mixed_stream_id'], $allParticipants);

                    return response()->json([
                        'success' => true,
                        'mixed_stream_id' => $result['mixed_stream_id'],
                        'task_id' => $result['task_id'],
                        'participants' => $allParticipants,
                        'layout_type' => $this->getLayoutType(count($allParticipants))
                    ]);
                }

                return response()->json($result, $result['success'] ? 200 : 500);
            }
        } catch (\Exception $e) {
            Log::error('Notify cohost joined error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual mixer güncelleme
     */
    public function updateMixer(Request $request)
    {
        try {
            $validated = $request->validate([
                'task_id' => 'required|string',
                'streamers' => 'required|array',
                'streamers.*.stream_id' => 'required|string',
                'streamers.*.user_id' => 'required|string',
                'streamers.*.chat_room_id' => 'required|string',
            ]);

            $result = $this->mixerService->updateMixerTask(
                $validated['task_id'],
                $validated['streamers']
            );

            if ($result['success']) {
                // Get original stream ID (first non-cohost stream)
                $originalStreamId = null;
                foreach ($validated['streamers'] as $streamer) {
                    if (!str_contains($streamer['stream_id'], 'cohost_')) {
                        $originalStreamId = $streamer['stream_id'];
                        break;
                    }
                }

                if ($originalStreamId) {
                    $this->broadcastMixerUpdate($originalStreamId, $result['mixed_stream_id'] ?? '', $validated['streamers']);
                }
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all participants for a stream (including existing cohosts)
     */
    private function getAllStreamParticipants($originalStreamId)
    {
        $participants = [];

        // Add original host
        $hostChannel = AgoraChannel::where('id', $originalStreamId)
            ->orWhere('channel_name', $originalStreamId)
            ->first();

        if ($hostChannel) {
            $participants[] = [
                'stream_id' => $hostChannel->id,
                'user_id' => $hostChannel->user_id,
                'chat_room_id' => 'chat_' . $hostChannel->id
            ];
        }

        // Find all active cohost streams related to this original stream
        $cohostStreams = DB::table('related_streams')
            ->where('host_stream_id', $originalStreamId)
            ->whereNull('ended_at')
            ->get();

        foreach ($cohostStreams as $cohost) {
            $cohostChannel = AgoraChannel::where('id', $cohost->cohost_stream_id)->first();
            if ($cohostChannel && $cohostChannel->is_online) {
                $participants[] = [
                    'stream_id' => $cohostChannel->id,
                    'user_id' => $cohostChannel->user_id,
                    'chat_room_id' => 'chat_' . $cohostChannel->id
                ];
            }
        }

        Log::info('All stream participants found', [
            'original_stream' => $originalStreamId,
            'participant_count' => count($participants),
            'participants' => $participants
        ]);

        return $participants;
    }

    /**
     * Store mixer info in database
     */
    private function storeMixerInfo($mixerResult, $participants)
    {
        try {
            // Store in related_streams table for tracking
            foreach ($participants as $index => $participant) {
                if (str_contains($participant['stream_id'], 'cohost_')) {
                    DB::table('related_streams')->updateOrInsert(
                        ['cohost_stream_id' => $participant['stream_id']],
                        [
                            'host_stream_id' => $participants[0]['stream_id'] ?? '', // First is always host
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }

            // Update AgoraChannel with mixer info
            foreach ($participants as $participant) {
                AgoraChannel::where('id', $participant['stream_id'])
                    ->update([
                        'mixed_stream_id' => $mixerResult['mixed_stream_id'],
                        'mixer_task_id' => $mixerResult['task_id'],
                        'updated_at' => now()
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store mixer info', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast mixer update to all participants
     */
    private function broadcastMixerUpdate($roomId, $mixedStreamId, $participants)
    {
        try {
            $eventData = [
                'room_id' => $roomId,
                'mixed_stream_id' => $mixedStreamId,
                'participants' => $participants,
                'layout_type' => $this->getLayoutType(count($participants)),
                'participant_count' => count($participants),
                'timestamp' => now()->toIso8601String()
            ];

            // Broadcast to main room
            broadcast(new MixedStreamUpdate($eventData))->toOthers();

            // Also broadcast to each cohost room
            foreach ($participants as $participant) {
                if (str_contains($participant['stream_id'], 'cohost_')) {
                    $cohostEventData = $eventData;
                    $cohostEventData['room_id'] = $participant['stream_id'];
                    broadcast(new MixedStreamUpdate($cohostEventData))->toOthers();
                }
            }

            Log::info('Mixed stream update broadcasted', [
                'room_id' => $roomId,
                'mixed_stream_id' => $mixedStreamId,
                'participant_count' => count($participants)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast mixer update', [
                'error' => $e->getMessage()
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
     * Get active mixer info for a stream
     */
    public function getMixerInfo($streamId)
    {
        try {
            $mixer = $this->mixerService->getActiveMixerForStream($streamId);

            if ($mixer) {
                return response()->json([
                    'success' => true,
                    'mixer' => $mixer
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active mixer found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}