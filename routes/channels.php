<?php

use Carbon\Carbon;
use App\Events\UserOnline;
use App\Models\Chat\Conversation;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    return $conversation !== null && in_array($user->id, $conversation->getParticipantIds());
});

// Kullanıcı bazlı genel bildirimler
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    if ($user->id == $id) {
        $socketId =  request()->input('socket_id');
        if (!$socketId) {
            Log::warning("Broadcast channel auth: socket_id parametre olarak gelmedi.");
            return false;
        }

        if (Redis::exists("socket-user:{$socketId}")) {
            return true;
        }


        $now = Carbon::now();

        $startAt = $now->toDateTimeString();
        $lastSeenAt = (clone $now)->timestamp;

        $key = "socket-user:{$socketId}";

        Redis::setex($key, 86400, json_encode([
            'user_id' => $user->id,
            'start_at' => $startAt,
            'last_seen_at' => $lastSeenAt,
        ]));
        Redis::sadd("active-users", $user->id);
        Redis::incr("active-socket-count:user:{$user->id}");

        // online oldu bilgisini broadcast ile diğer kişilerine gönder
        foreach ($user->allConversationsUserIds() as $receiverId) {
            broadcast(new UserOnline($receiverId));
        }

        Log::info("User {$user->id} joined channel {$socketId} at {$startAt}. last seen at {$lastSeenAt}");

        return true;
    }

    return false;
});

Broadcast::channel('live-stream.{agoraChannelId}', function ($user, $agoraChannelId) {
    return true;
});

// canlı yayın hediye kanalı
Broadcast::channel('live-stream.{agoraChannelId}.gifts', function ($user, $agoraChannelId) {
    return AgoraChannel::where('is_active')->where('id', $agoraChannelId)->first() !== null;
});

// canlı yayın yorum kanalı (ban vs bildirimleri de buradan gidebilir)
Broadcast::channel('live-stream.{agoraChannelId}.chat', function ($user, $agoraChannelId) {
    return AgoraChannel::where('is_active')->where('id', $agoraChannelId)->first() !== null;
});

/* start::Admin*/
Broadcast::channel('App.Models.Admin.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['admin']]);
/* end::Admin*/