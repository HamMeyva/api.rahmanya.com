<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Models\User;
use App\Services\ZegoStreamMixerService;
use App\Services\LiveStream\AgoraChannelService;
use App\Services\StreamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CohostStreamController extends Controller
{
    private ZegoStreamMixerService $mixerService;
    private AgoraChannelService $channelService;
    private StreamService $streamService;

    public function __construct(
        ZegoStreamMixerService $mixerService,
        AgoraChannelService $channelService,
        StreamService $streamService
    ) {
        $this->mixerService = $mixerService;
        $this->channelService = $channelService;
        $this->streamService = $streamService;
    }

    /**
     * Accept invite and start co-host stream
     */
    public function acceptInviteAndStartStream(Request $request): JsonResponse
    {
        $request->validate([
            'invite_id' => 'required|uuid'
        ]);

        DB::beginTransaction();

        try {
            // Get and validate invite
            $invite = DB::table('live_stream_invites')
                ->where('id', $request->invite_id)
                ->where('invited_user_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if (!$invite) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired invite'
                ], 400);
            }

            // Get host stream
            $hostStream = AgoraChannel::where('channel_name', $invite->stream_id)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$hostStream) {
                // Update invite as expired if host stream ended
                DB::table('live_stream_invites')
                    ->where('id', $request->invite_id)
                    ->update([
                        'status' => 'expired',
                        'expired_at' => now(),
                        'updated_at' => now()
                    ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'error' => 'Host stream is no longer live'
                ], 400);
            }

            // Generate unique IDs for co-host stream
            $cohostChannelName = 'cohost_' . Str::uuid()->toString();
            $cohostChatRoomId = 'chat_' . $cohostChannelName;

            // Start co-host stream using channel service
            $cohostStream = $this->channelService->startStream(Auth::user(), [
                'title' => 'Co-hosting with ' . ($hostStream->title ?? 'Live Stream'),
                'description' => 'Joined as co-host',
                'category_id' => $hostStream->category_id,
                'is_cohost' => true,
                'host_stream_id' => $hostStream->channel_name,
                'channel_name' => $cohostChannelName // Specify custom channel name
            ]);

            if (!$cohostStream) {
                throw new \Exception('Failed to create co-host stream');
            }

            // Update invite status
            DB::table('live_stream_invites')
                ->where('id', $request->invite_id)
                ->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'updated_at' => now()
                ]);

            // Prepare mixer participants
            $participants = [
                [
                    'stream_id' => $hostStream->channel_name,
                    'user_id' => $hostStream->user_id,
                    'chat_room_id' => 'chat_' . $hostStream->channel_name
                ],
                [
                    'stream_id' => $cohostChannelName,
                    'user_id' => Auth::id(),
                    'chat_room_id' => $cohostChatRoomId
                ]
            ];

            // Check if there's already an active mixer session for the host
            $existingMixer = DB::table('mixer_participants as mp')
                ->join('mixer_sessions as ms', 'mp.mixer_session_id', '=', 'ms.id')
                ->where('mp.stream_id', $hostStream->channel_name)
                ->where('ms.status', 'active')
                ->whereNull('mp.left_at')
                ->first();

            if ($existingMixer) {
                // Get existing participants and add new one
                $existingParticipants = DB::table('mixer_participants')
                    ->where('mixer_session_id', $existingMixer->mixer_session_id)
                    ->whereNull('left_at')
                    ->get()
                    ->map(function($p) {
                        return [
                            'stream_id' => $p->stream_id,
                            'user_id' => $p->user_id,
                            'chat_room_id' => $p->chat_room_id
                        ];
                    })
                    ->toArray();

                // Add new co-host
                $existingParticipants[] = [
                    'stream_id' => $cohostChannelName,
                    'user_id' => Auth::id(),
                    'chat_room_id' => $cohostChatRoomId
                ];

                // Update mixer with all participants
                $mixerResult = $this->mixerService->updateMixerTask(
                    $existingMixer->task_id,
                    $existingParticipants
                );
            } else {
                // Start new mixer with both streams
                $mixerResult = $this->mixerService->startMixerTask($participants);
            }

            // Get mixer session info
            $mixerInfo = null;
            if ($mixerResult['success']) {
                $mixerInfo = $this->mixerService->getActiveMixerForStream($cohostChannelName);
            }

            DB::commit();

            Log::info('Co-host stream started successfully', [
                'invite_id' => $request->invite_id,
                'host_stream' => $hostStream->channel_name,
                'cohost_stream' => $cohostChannelName,
                'mixer_session' => $mixerInfo['session_id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Co-host stream started successfully',
                'data' => [
                    'cohost_stream' => [
                        'channel_name' => $cohostChannelName,
                        'chat_room_id' => $cohostChatRoomId,
                        'agora_channel_id' => $cohostStream->id,
                        'is_cohost' => true,
                        'host_stream_id' => $hostStream->channel_name
                    ],
                    'host_stream' => [
                        'channel_name' => $hostStream->channel_name,
                        'title' => $hostStream->title,
                        'user' => [
                            'id' => $hostStream->user_id,
                            'username' => $hostStream->user->username ?? 'Host'
                        ]
                    ],
                    'mixer_session' => $mixerInfo
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Accept invite and start stream error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invite_id' => $request->invite_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start co-host stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decline invite
     */
    public function declineInvite(Request $request): JsonResponse
    {
        $request->validate([
            'invite_id' => 'required|uuid'
        ]);

        try {
            $updated = DB::table('live_stream_invites')
                ->where('id', $request->invite_id)
                ->where('invited_user_id', Auth::id())
                ->where('status', 'pending')
                ->update([
                    'status' => 'declined',
                    'declined_at' => now(),
                    'updated_at' => now()
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or already processed invite'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invite declined successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Decline invite error', [
                'error' => $e->getMessage(),
                'invite_id' => $request->invite_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to decline invite'
            ], 500);
        }
    }

    /**
     * Leave co-host session
     */
    public function leaveCohostSession(Request $request): JsonResponse
    {
        $request->validate([
            'channel_name' => 'required|string'
        ]);

        DB::beginTransaction();

        try {
            // Get co-host stream
            $cohostStream = AgoraChannel::where('channel_name', $request->channel_name)
                ->where('user_id', Auth::id())
                ->where('is_cohost', true)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$cohostStream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Co-host stream not found'
                ], 404);
            }

            // Get mixer info and remove from it
            $mixerInfo = $this->mixerService->getActiveMixerForStream($cohostStream->channel_name);

            if ($mixerInfo) {
                // Get remaining participants
                $remainingParticipants = array_filter($mixerInfo['participants'], function($p) use ($cohostStream) {
                    return $p['stream_id'] !== $cohostStream->channel_name;
                });

                if (count($remainingParticipants) > 0) {
                    // Update mixer with remaining participants
                    $this->mixerService->updateMixerTask(
                        $mixerInfo['task_id'],
                        array_values($remainingParticipants)
                    );
                } else {
                    // Stop mixer if no participants left
                    $this->mixerService->stopMixerTask($mixerInfo['task_id']);
                }
            }

            // End co-host stream
            $this->channelService->endStream($cohostStream);

            // Update any active invites related to this session
            if ($cohostStream->host_stream_id) {
                DB::table('live_stream_invites')
                    ->where('stream_id', $cohostStream->host_stream_id)
                    ->where('invited_user_id', Auth::id())
                    ->where('status', 'accepted')
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            Log::info('Co-host left session', [
                'channel_name' => $request->channel_name,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left co-host session'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Leave co-host session error', [
                'error' => $e->getMessage(),
                'channel_name' => $request->channel_name
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to leave co-host session'
            ], 500);
        }
    }

    /**
     * Get active co-host sessions for a stream (PUBLIC - no auth)
     */
    public function getActiveCoHostsPublic(Request $request, $channel_name): JsonResponse
    {
        return $this->getActiveCoHosts($request, $channel_name);
    }

    /**
     * Get active co-host sessions for a stream
     */
    public function getActiveCoHosts(Request $request, $channel_name): JsonResponse
    {
        try {
            // Get requesting user info
            $requestingUserId = Auth::check() ? Auth::id() : null;
            $requestingStreamId = $request->get('requesting_stream_id');

            // Use StreamService to get participants with self-filtering
            $participantsData = $this->streamService->getActiveParticipants(
                $channel_name,
                $requestingUserId,
                $requestingStreamId
            );

            if (!$participantsData['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $participantsData['error'] ?? 'Failed to get participants'
                ], 404);
            }

            // Get mixer info if exists
            $mixerInfo = $this->mixerService->getActiveMixerForStream($channel_name);

            // Prepare response with filtered participants
            $hostStream = $participantsData['host_stream'];
            $filteredParticipants = $participantsData['participants'];

            // Separate host and cohosts for backward compatibility
            $cohosts = array_filter($filteredParticipants, function($p) {
                return !($p['is_host'] ?? false);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'main_stream' => $hostStream ? [
                        'channel_name' => $hostStream['stream_id'],
                        'title' => $hostStream['title'],
                        'viewer_count' => 0 // You may want to get this from DB
                    ] : null,
                    'other_participants' => $filteredParticipants, // NEW: Filtered list
                    'cohosts' => array_values($cohosts),  // Filtered cohosts
                    'co_hosts' => array_values($cohosts),  // Backward compatibility
                    'mixer_session' => $mixerInfo,
                    'total_streamers' => $participantsData['total_participants'],
                    'is_requesting_user_host' => $participantsData['is_requester_host']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get active co-hosts error', [
                'error' => $e->getMessage(),
                'channel_name' => $channel_name
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get co-hosts'
            ], 500);
        }
    }

    /**
     * Remove a co-host (host only)
     */
    public function removeCoHost(Request $request): JsonResponse
    {
        $request->validate([
            'host_channel_name' => 'required|string',
            'cohost_user_id' => 'required|uuid'
        ]);

        DB::beginTransaction();

        try {
            // Verify requester is the host
            $hostStream = AgoraChannel::where('channel_name', $request->host_channel_name)
                ->where('user_id', Auth::id())
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$hostStream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized or stream not found'
                ], 403);
            }

            // Find co-host stream
            $cohostStream = AgoraChannel::where('user_id', $request->cohost_user_id)
                ->where('is_cohost', true)
                ->where('host_stream_id', $request->host_channel_name)
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$cohostStream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Co-host not found'
                ], 404);
            }

            // Get mixer info and remove from it
            $mixerInfo = $this->mixerService->getActiveMixerForStream($cohostStream->channel_name);

            if ($mixerInfo) {
                // Get remaining participants
                $remainingParticipants = array_filter($mixerInfo['participants'], function($p) use ($cohostStream) {
                    return $p['stream_id'] !== $cohostStream->channel_name;
                });

                if (count($remainingParticipants) > 0) {
                    // Update mixer with remaining participants
                    $this->mixerService->updateMixerTask(
                        $mixerInfo['task_id'],
                        array_values($remainingParticipants)
                    );
                } else {
                    // Stop mixer if no participants left
                    $this->mixerService->stopMixerTask($mixerInfo['task_id']);
                }
            }

            // End co-host stream
            $this->channelService->endStream($cohostStream);

            // Update invite status
            DB::table('live_stream_invites')
                ->where('stream_id', $request->host_channel_name)
                ->where('invited_user_id', $request->cohost_user_id)
                ->where('status', 'accepted')
                ->update([
                    'status' => 'removed',
                    'removed_at' => now(),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Co-host removed by host', [
                'host_channel' => $request->host_channel_name,
                'cohost_user_id' => $request->cohost_user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Co-host removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Remove co-host error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to remove co-host'
            ], 500);
        }
    }
}