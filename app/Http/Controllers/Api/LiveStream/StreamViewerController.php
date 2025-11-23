<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Services\ZegoStreamMixerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StreamViewerController extends Controller
{
    private ZegoStreamMixerService $mixerService;

    public function __construct(ZegoStreamMixerService $mixerService)
    {
        $this->mixerService = $mixerService;
    }

    /**
     * Get stream viewing options for a stream
     * Returns both mixed stream info and individual stream options
     */
    public function getStreamOptions(Request $request): JsonResponse
    {
        $request->validate([
            'channel_name' => 'required|string'
        ]);

        try {
            // Get the requested stream
            $stream = AgoraChannel::where('channel_name', $request->channel_name)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->with('user:id,username,nickname,avatar')
                ->first();

            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Stream not found or not live'
                ], 404);
            }

            // Check if this stream is part of a mixer
            $mixerInfo = $this->mixerService->getActiveMixerForStream($request->channel_name);

            $response = [
                'success' => true,
                'data' => [
                    'requested_stream' => [
                        'channel_name' => $stream->channel_name,
                        'title' => $stream->title,
                        'chat_room_id' => 'chat_' . $stream->channel_name,
                        'user' => [
                            'id' => $stream->user->id,
                            'username' => $stream->user->username ?? $stream->user->nickname,
                            'avatar' => $stream->user->avatar
                        ],
                        'viewer_count' => $stream->viewer_count,
                        'is_cohost' => $stream->is_cohost
                    ]
                ]
            ];

            if ($mixerInfo) {
                // Stream is part of a mixer
                $response['data']['has_mixer'] = true;
                $response['data']['mixer'] = [
                    'mixed_stream_id' => $mixerInfo['mixed_stream_id'],
                    'layout_type' => $mixerInfo['layout_type'],
                    'total_streamers' => count($mixerInfo['participants'])
                ];

                // Get all individual streams in the mixer
                $individualStreams = [];
                foreach ($mixerInfo['participants'] as $participant) {
                    $participantStream = AgoraChannel::where('channel_name', $participant['stream_id'])
                        ->where('status_id', AgoraChannel::STATUS_LIVE)
                        ->with('user:id,username,nickname,avatar')
                        ->first();

                    if ($participantStream) {
                        $individualStreams[] = [
                            'channel_name' => $participantStream->channel_name,
                            'chat_room_id' => $participant['chat_room_id'],
                            'title' => $participantStream->title,
                            'user' => [
                                'id' => $participantStream->user->id,
                                'username' => $participantStream->user->username ?? $participantStream->user->nickname,
                                'avatar' => $participantStream->user->avatar
                            ],
                            'position' => $participant['position'],
                            'is_host' => !$participantStream->is_cohost
                        ];
                    }
                }

                $response['data']['individual_streams'] = $individualStreams;

                // Determine default view mode
                $response['data']['default_view'] = 'mixed'; // Default to mixed view
            } else {
                // No mixer, just single stream
                $response['data']['has_mixer'] = false;
                $response['data']['default_view'] = 'single';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Get stream options error', [
                'error' => $e->getMessage(),
                'channel_name' => $request->channel_name
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get stream options'
            ], 500);
        }
    }

    /**
     * Get stats for a specific stream (viewer count, duration, etc.)
     */
    public function getStreamStats(Request $request): JsonResponse
    {
        $request->validate([
            'channel_name' => 'required|string'
        ]);

        try {
            $stream = AgoraChannel::where('channel_name', $request->channel_name)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Stream not found'
                ], 404);
            }

            // Calculate stream duration
            $duration = $stream->started_at ? now()->diffInSeconds($stream->started_at) : 0;

            // Get mixer info if exists
            $mixerInfo = $this->mixerService->getActiveMixerForStream($request->channel_name);
            $totalViewers = $stream->viewer_count;

            if ($mixerInfo) {
                // Sum up viewers from all streams in mixer
                $totalViewers = DB::table('agora_channels')
                    ->whereIn('channel_name', array_column($mixerInfo['participants'], 'stream_id'))
                    ->where('status_id', AgoraChannel::STATUS_LIVE)
                    ->sum('viewer_count');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'channel_name' => $stream->channel_name,
                    'viewer_count' => $stream->viewer_count,
                    'total_viewers' => $totalViewers,
                    'duration_seconds' => $duration,
                    'started_at' => $stream->started_at,
                    'is_mixed' => $mixerInfo !== null,
                    'mixer_participant_count' => $mixerInfo ? count($mixerInfo['participants']) : 1
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get stream stats error', [
                'error' => $e->getMessage(),
                'channel_name' => $request->channel_name
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get stream stats'
            ], 500);
        }
    }

    /**
     * Update viewer count for a stream
     */
    public function updateViewerCount(Request $request): JsonResponse
    {
        $request->validate([
            'channel_name' => 'required|string',
            'action' => 'required|in:join,leave'
        ]);

        try {
            $stream = AgoraChannel::where('channel_name', $request->channel_name)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Stream not found'
                ], 404);
            }

            // Update viewer count
            if ($request->action === 'join') {
                $stream->increment('viewer_count');
            } else {
                $stream->decrement('viewer_count');
                // Ensure viewer count doesn't go below 0
                if ($stream->viewer_count < 0) {
                    $stream->update(['viewer_count' => 0]);
                }
            }

            return response()->json([
                'success' => true,
                'viewer_count' => $stream->fresh()->viewer_count
            ]);

        } catch (\Exception $e) {
            Log::error('Update viewer count error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update viewer count'
            ], 500);
        }
    }
}