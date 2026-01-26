<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Simplified invite controller that doesn't interfere with existing streams
 */
class SimpleLiveStreamInviteController extends Controller
{
    private FirebaseNotificationService $notificationService;

    public function __construct(FirebaseNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Send a simple invite without any stream manipulation
     */
    public function sendSimpleInvite(Request $request): JsonResponse
    {
        $request->validate([
            'channel_name' => 'required|string',
            'invited_user_id' => 'required|uuid|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        try {
            // Just verify the stream exists and belongs to the user
            $stream = AgoraChannel::where('channel_name', $request->channel_name)
                ->where('user_id', Auth::id())
                ->where('status_id', AgoraChannel::STATUS_LIVE)
                ->first();

            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active stream found'
                ], 404);
            }

            $invitedUser = User::find($request->invited_user_id);
            if (!$invitedUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Check for existing pending invite
            $existingInvite = DB::table('live_stream_invites')
                ->where('stream_id', $request->channel_name)
                ->where('invited_user_id', $request->invited_user_id)
                ->where('status', 'pending')
                ->first();

            if ($existingInvite) {
                return response()->json([
                    'success' => false,
                    'error' => 'User already has a pending invite'
                ], 400);
            }

            // Create invite record only - no stream manipulation
            $inviteId = Str::uuid()->toString();
            DB::table('live_stream_invites')->insert([
                'id' => $inviteId,
                'stream_id' => $request->channel_name,
                'host_user_id' => Auth::id(),
                'invited_user_id' => $request->invited_user_id,
                'message' => $request->message,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Simple invite created', [
                'invite_id' => $inviteId,
                'stream_id' => $request->channel_name,
                'host_user_id' => Auth::id(),
                'invited_user_id' => $request->invited_user_id
            ]);

            // Send push notification to invited user
            if ($invitedUser->fcm_token) {
                $hostName = Auth::user()->username ?? Auth::user()->nickname ?? 'Bir kullanıcı';

                Log::info('Sending simple invite notification', [
                    'to_user' => $invitedUser->id,
                    'token' => substr($invitedUser->fcm_token, 0, 10) . '...'
                ]);

                $this->notificationService->sendToDevice(
                    $invitedUser->fcm_token,
                    'Canlı Yayın Daveti',
                    "{$hostName} seni canlı yayınına davet etti! 🎥",
                    [
                        'type' => 'live_stream_invite',
                        'invite_id' => $inviteId,
                        'stream_id' => $request->channel_name, // Using channel_name as stream_id for consistency
                        'host_name' => $hostName,
                        'host_avatar' => Auth::user()->avatar ?? '',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Invite sent successfully',
                'data' => [
                    'invite_id' => $inviteId,
                    'channel_name' => $request->channel_name,
                    'invited_user' => [
                        'id' => $invitedUser->id,
                        'username' => $invitedUser->username ?? $invitedUser->nickname ?? 'User',
                        'avatar' => $invitedUser->avatar
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Send simple invite error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send invite: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List pending invites for the current user
     */
    public function getMyInvites(): JsonResponse
    {
        try {
            $invites = DB::table('live_stream_invites as i')
                ->join('users as host', 'i.host_user_id', '=', 'host.id')
                ->leftJoin('agora_channels as ac', function ($join) {
                    $join->on('i.stream_id', '=', 'ac.channel_name')
                        ->where('ac.status_id', '=', AgoraChannel::STATUS_LIVE);
                })
                ->where('i.invited_user_id', Auth::id())
                ->where('i.status', 'pending')
                ->whereNotNull('ac.id') // Only show invites for active streams
                ->select(
                    'i.id as invite_id',
                    'i.stream_id as channel_name',
                    'i.message',
                    'i.created_at',
                    'host.id as host_id',
                    'host.username as host_username',
                    'host.nickname as host_nickname',
                    'host.avatar as host_avatar',
                    'ac.title as stream_title',
                    'ac.viewer_count'
                )
                ->orderBy('i.created_at', 'desc')
                ->get();

            // Format the response
            $formattedInvites = $invites->map(function ($invite) {
                return [
                    'invite_id' => $invite->invite_id,
                    'channel_name' => $invite->channel_name,
                    'message' => $invite->message,
                    'created_at' => $invite->created_at,
                    'host' => [
                        'id' => $invite->host_id,
                        'username' => $invite->host_username ?? $invite->host_nickname ?? 'User',
                        'avatar' => $invite->host_avatar
                    ],
                    'stream' => [
                        'title' => $invite->stream_title ?? 'Live Stream',
                        'viewer_count' => $invite->viewer_count ?? 0
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedInvites
            ]);
        } catch (\Exception $e) {
            Log::error('Get my invites error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get invites'
            ], 500);
        }
    }

    /**
     * Cancel an invite (for host)
     */
    public function cancelInvite(Request $request): JsonResponse
    {
        $request->validate([
            'invite_id' => 'required|uuid'
        ]);

        try {
            $updated = DB::table('live_stream_invites')
                ->where('id', $request->invite_id)
                ->where('host_user_id', Auth::id())
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'expired_at' => now(),
                    'updated_at' => now()
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invite not found or already processed'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invite cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cancel invite error', [
                'error' => $e->getMessage(),
                'invite_id' => $request->invite_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel invite'
            ], 500);
        }
    }
}