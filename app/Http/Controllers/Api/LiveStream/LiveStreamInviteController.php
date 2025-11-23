<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Models\User;
use App\Services\ZegoMixerService;
use App\Services\LiveStream\AgoraChannelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LiveStreamInviteController extends Controller
{
    private ZegoMixerService $mixerService;
    private AgoraChannelService $channelService;

    public function __construct(
        ZegoMixerService $mixerService,
        AgoraChannelService $channelService
    ) {
        $this->mixerService = $mixerService;
        $this->channelService = $channelService;
    }

    /**
     * Send invite to another user to join stream
     */
    public function sendInvite(Request $request): JsonResponse
    {
        $request->validate([
            'stream_id' => 'required|string',
            'invited_user_id' => 'required|uuid|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        try {
            $stream = AgoraChannel::where('channel_name', $request->stream_id)
                ->where('user_id', Auth::id())
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->firstOrFail();

            $invitedUser = User::findOrFail($request->invited_user_id);

            // Check if user is already invited or streaming
            $existingInvite = DB::table('live_stream_invites')
                ->where('stream_id', $request->stream_id)
                ->where('invited_user_id', $request->invited_user_id)
                ->where('status', 'pending')
                ->first();

            if ($existingInvite) {
                return response()->json([
                    'success' => false,
                    'error' => 'User already has a pending invite'
                ], 400);
            }

            // Create invite
            $inviteId = Str::uuid();
            DB::table('live_stream_invites')->insert([
                'id' => $inviteId,
                'stream_id' => $request->stream_id,
                'host_user_id' => Auth::id(),
                'invited_user_id' => $request->invited_user_id,
                'message' => $request->message,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Send notification to invited user (implement based on your notification system)
            // $this->notificationService->sendInviteNotification($invitedUser, $stream, $request->message);

            return response()->json([
                'success' => true,
                'data' => [
                    'invite_id' => $inviteId,
                    'stream_id' => $request->stream_id,
                    'invited_user' => [
                        'id' => $invitedUser->id,
                        'username' => $invitedUser->username,
                        'avatar' => $invitedUser->avatar
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Send invite error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send invite'
            ], 500);
        }
    }

    /**
     * Accept stream invite and join mixer
     */
    public function acceptInvite(Request $request): JsonResponse
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
                ->where('status', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$hostStream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Host stream is no longer live'
                ], 400);
            }

            // Create new stream for invited user
            $newStreamId = 'stream_' . Auth::id() . '_' . time();
            $chatRoomId = 'chat_' . $newStreamId;

            // Start stream for invited user using your channel service
            $invitedStream = $this->channelService->startStream(Auth::user(), [
                'title' => 'Co-hosting with ' . $hostStream->title,
                'description' => 'Joined as co-host',
                'category_id' => $hostStream->category_id,
                'is_cohost' => true,
                'host_stream_id' => $hostStream->stream_id,
                'chat_room_id' => $chatRoomId
            ]);

            if (!$invitedStream) {
                throw new \Exception('Failed to create stream for invited user');
            }

            // Update invite status
            DB::table('live_stream_invites')
                ->where('id', $request->invite_id)
                ->update([
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'updated_at' => now()
                ]);

            // Add to mixer session
            $mixerResult = $this->mixerService->handleStreamerJoin(
                $invitedStream->channel_name,
                Auth::id(),
                $chatRoomId
            );

            // Get mixer session info
            $mixerInfo = null;
            if ($mixerResult['success'] && isset($mixerResult['session_id'])) {
                $mixerInfo = $this->mixerService->getMixerInfoForStream($invitedStream->channel_name);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'stream_id' => $invitedStream->channel_name,
                    'chat_room_id' => $chatRoomId,
                    'channel_name' => $invitedStream->channel_name,
                    'token' => $invitedStream->token ?? null,
                    'mixer_session' => $mixerInfo,
                    'host_stream' => [
                        'stream_id' => $hostStream->channel_name,
                        'title' => $hostStream->title,
                        'user' => [
                            'id' => $hostStream->user_id,
                            'username' => $hostStream->user->username ?? 'Unknown'
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Accept invite error', [
                'error' => $e->getMessage(),
                'invite_id' => $request->invite_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to accept invite'
            ], 500);
        }
    }

    /**
     * Decline stream invite
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
                    'error' => 'Invalid or expired invite'
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
     * Get pending invites for current user
     */
    public function getPendingInvites(): JsonResponse
    {
        try {
            $invites = DB::table('live_stream_invites as i')
                ->join('users as host', 'i.host_user_id', '=', 'host.id')
                ->join('agora_channels as ac', 'i.stream_id', '=', 'ac.channel_name')
                ->where('i.invited_user_id', Auth::id())
                ->where('i.status', 'pending')
                ->where('ac.status_id', AgoraChannel::STATUS_LIVE)
                ->select(
                    'i.id as invite_id',
                    'i.stream_id',
                    'i.message',
                    'i.created_at',
                    'host.id as host_id',
                    'host.username as host_username',
                    'host.avatar as host_avatar',
                    'ac.title as stream_title',
                    'ac.viewer_count'
                )
                ->orderBy('i.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $invites
            ]);
        } catch (\Exception $e) {
            Log::error('Get pending invites error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get pending invites'
            ], 500);
        }
    }

    /**
     * Remove co-host from stream
     */
    public function removeCohost(Request $request): JsonResponse
    {
        $request->validate([
            'stream_id' => 'required|string',
            'cohost_user_id' => 'required|uuid'
        ]);

        try {
            // Verify requester is the host
            $hostStream = AgoraChannel::where('channel_name', $request->stream_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Find cohost stream
            $cohostStream = AgoraChannel::where('user_id', $request->cohost_user_id)
                ->where('is_cohost', true)
                ->where('host_stream_id', $request->stream_id)
                ->first();

            if (!$cohostStream) {
                return response()->json([
                    'success' => false,
                    'error' => 'Co-host not found'
                ], 404);
            }

            // Remove from mixer
            $this->mixerService->handleStreamerLeave($cohostStream->channel_name);

            // End cohost stream
            $this->channelService->endStream($cohostStream);

            // Update invite status
            DB::table('live_stream_invites')
                ->where('stream_id', $request->stream_id)
                ->where('invited_user_id', $request->cohost_user_id)
                ->where('status', 'accepted')
                ->update([
                    'status' => 'removed',
                    'removed_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Co-host removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Remove cohost error', [
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