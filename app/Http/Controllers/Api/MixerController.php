<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZegoMixerService;
use App\Models\MixerSession;
use App\Models\MixerParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MixerController extends Controller
{
    private ZegoMixerService $mixerService;

    public function __construct(ZegoMixerService $mixerService)
    {
        $this->mixerService = $mixerService;
    }

    /**
     * Get mixer session details
     */
    public function getSession(string $sessionId): JsonResponse
    {
        try {
            $session = MixerSession::with(['activeParticipants.user'])
                ->findOrFail($sessionId);

            $participants = $session->activeParticipants->map(function ($participant) {
                return [
                    'stream_id' => $participant->stream_id,
                    'user_id' => $participant->user_id,
                    'chat_room_id' => $participant->chat_room_id,
                    'position' => $participant->position,
                    'username' => $participant->user->username ?? 'Unknown',
                    'avatar' => $participant->user->avatar ?? null,
                    'joined_at' => $participant->joined_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->id,
                    'task_id' => $session->task_id,
                    'mixed_stream_id' => $session->mixed_stream_id,
                    'mixed_stream_url' => $session->mixed_stream_url,
                    'layout_type' => $session->layout_type,
                    'status' => $session->status,
                    'participants' => $participants,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get mixer session error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Session not found'
            ], 404);
        }
    }

    /**
     * Get active mixer by stream ID
     */
    public function getByStreamId(string $streamId): JsonResponse
    {
        try {
            $mixerInfo = $this->mixerService->getMixerInfoForStream($streamId);

            if (!$mixerInfo) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active mixer found for this stream'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mixerInfo
            ]);
        } catch (\Exception $e) {
            Log::error('Get mixer by stream error', [
                'stream_id' => $streamId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get mixer information'
            ], 500);
        }
    }

    /**
     * Start a new mixer session
     */
    public function startMixer(Request $request): JsonResponse
    {
        $request->validate([
            'streamers' => 'required|array|min:1|max:4',
            'streamers.*.stream_id' => 'required|string',
            'streamers.*.user_id' => 'required|uuid',
            'streamers.*.chat_room_id' => 'required|string'
        ]);

        try {
            $result = $this->mixerService->startMixer($request->streamers);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Start mixer error', [
                'streamers' => $request->streamers,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start mixer'
            ], 500);
        }
    }

    /**
     * Update mixer participants
     */
    public function updateMixer(Request $request, string $taskId): JsonResponse
    {
        $request->validate([
            'streamers' => 'required|array|max:4',
            'streamers.*.stream_id' => 'required|string',
            'streamers.*.user_id' => 'required|uuid',
            'streamers.*.chat_room_id' => 'required|string'
        ]);

        try {
            $result = $this->mixerService->updateMixer($taskId, $request->streamers);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Update mixer error', [
                'task_id' => $taskId,
                'streamers' => $request->streamers,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update mixer'
            ], 500);
        }
    }

    /**
     * Stop mixer session
     */
    public function stopMixer(string $taskId): JsonResponse
    {
        try {
            $result = $this->mixerService->stopMixer($taskId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Stop mixer error', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to stop mixer'
            ], 500);
        }
    }

    /**
     * Handle streamer join
     */
    public function handleStreamerJoin(Request $request): JsonResponse
    {
        $request->validate([
            'stream_id' => 'required|string',
            'user_id' => 'required|uuid',
            'chat_room_id' => 'required|string'
        ]);

        try {
            $result = $this->mixerService->handleStreamerJoin(
                $request->stream_id,
                $request->user_id,
                $request->chat_room_id
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Handle streamer join error', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to handle streamer join'
            ], 500);
        }
    }

    /**
     * Handle streamer leave
     */
    public function handleStreamerLeave(Request $request): JsonResponse
    {
        $request->validate([
            'stream_id' => 'required|string'
        ]);

        try {
            $result = $this->mixerService->handleStreamerLeave($request->stream_id);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Handle streamer leave error', [
                'stream_id' => $request->stream_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to handle streamer leave'
            ], 500);
        }
    }

    /**
     * Get all active mixer sessions
     */
    public function getActiveSessions(): JsonResponse
    {
        try {
            $sessions = MixerSession::where('status', 'active')
                ->with(['activeParticipants.user'])
                ->get()
                ->map(function ($session) {
                    return [
                        'session_id' => $session->id,
                        'task_id' => $session->task_id,
                        'mixed_stream_id' => $session->mixed_stream_id,
                        'mixed_stream_url' => $session->mixed_stream_url,
                        'layout_type' => $session->layout_type,
                        'participant_count' => $session->participant_count,
                        'participants' => $session->activeParticipants->map(function ($p) {
                            return [
                                'stream_id' => $p->stream_id,
                                'username' => $p->user->username ?? 'Unknown',
                                'avatar' => $p->user->avatar ?? null
                            ];
                        }),
                        'created_at' => $session->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            Log::error('Get active sessions error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get active sessions'
            ], 500);
        }
    }

    /**
     * Reconfigure mixer (admin function)
     */
    public function reconfigureMixer(string $taskId): JsonResponse
    {
        try {
            $session = MixerSession::where('task_id', $taskId)->firstOrFail();

            $participants = MixerParticipant::where('mixer_session_id', $session->id)
                ->whereNull('left_at')
                ->get()
                ->map(fn($p) => [
                    'stream_id' => $p->stream_id,
                    'user_id' => $p->user_id,
                    'chat_room_id' => $p->chat_room_id
                ])
                ->toArray();

            $result = $this->mixerService->updateMixer($taskId, $participants);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Reconfigure mixer error', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reconfigure mixer'
            ], 500);
        }
    }
}