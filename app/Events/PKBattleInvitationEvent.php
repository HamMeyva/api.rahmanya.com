<?php

namespace App\Events;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleInvitationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;

    public function __construct(PKBattle $battle)
    {
        $this->battle = $battle;
    }

    public function broadcastOn()
    {
        $channels = [];

        // 1. Broadcast to the opponent's user channel for push notification
        $channels[] = new PresenceChannel('user.' . $this->battle->opponent_id);

        // 2. Broadcast to host/challenger stream (host will see it)
        $channels[] = new Channel('live-stream.' . $this->battle->live_stream_id);

        // 3. ✅ CRITICAL: Broadcast to opponent stream (cohost will see it!)
        if ($this->battle->opponent_stream_id) {
            $channels[] = new Channel('live-stream.' . $this->battle->opponent_stream_id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'pk.battle.invitation';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->battle->id,
            'liveStreamId' => $this->battle->live_stream_id,
            'challengerId' => $this->battle->challenger_id,
            'challengerName' => $this->battle->challenger ? $this->battle->challenger->nickname : 'Unknown',
            'challengerAvatar' => $this->battle->challenger ? $this->battle->challenger->avatar : null,
            'opponentId' => $this->battle->opponent_id,
            'status' => $this->battle->status,
            'message' => $this->battle->challenger ? ($this->battle->challenger->nickname . ' sizi PK savaşına davet ediyor!') : 'PK savaşı daveti aldınız!',
            'createdAt' => $this->battle->created_at,
        ];
    }
}