<?php

namespace App\GraphQL\Mutations;

use App\Models\LiveStream;
use App\Models\User;
use App\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ZegoRoomInvite
{
    public function __invoke($rootValue, array $args, $context, $resolveInfo)
    {
        $agoraChannelId = $args['input']['liveStreamId'];
        $guestId = $args['input']['guestId'];
        $roomId = $args['input']['roomId'];
        $inviter = Auth::user();

        $agoraChannel = \App\Models\AgoraChannel::findOrFail($agoraChannelId);
        $guest = User::findOrFail($guestId);

        if ($agoraChannel->user_id !== $inviter->id) {
            return [
                'success' => false,
                'message' => 'Unauthorized',
            ];
        }

        DB::table('zego_room_invites')->insert([
            'agora_channel_id' => $agoraChannelId,
            'inviter_id' => $inviter->id,
            'invitee_id' => $guestId,
            'room_id' => $roomId,
            'status' => 'pending',
            'notification_data' => json_encode([
                'zego_room_id' => $roomId,
                'stream_id' => $agoraChannelId,
                'host_name' => $inviter->nickname ?? $inviter->name,
            ]),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        broadcast(new BroadcastNotificationCreated(
            userId: $guestId,
            title: $inviter->nickname . ' sizi yayına davet etti',
            body: 'Yayına katılmak için tıklayın',
            data: [
                'zego_room_id' => $roomId,
                'stream_id' => $agoraChannelId,
                'host_name' => $inviter->nickname ?? $inviter->name,
            ],
            type: 'zego_room_invite'
        ));

        return [
            'success' => true,
            'message' => 'Invite sent',
        ];
    }
}
