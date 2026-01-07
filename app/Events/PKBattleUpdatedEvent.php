<?php

namespace App\Events;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;

    public function __construct(PKBattle $battle)
    {
        $this->battle = $battle;
    }

    public function broadcastOn()
    {
        return [
            new Channel('live-stream.' . $this->battle->challenger_stream_id),
            new Channel('live-stream.' . $this->battle->challenged_stream_id),
        ];
    }

    public function broadcastAs()
    {
        return 'pk.battle.updated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->battle->id,
            'status' => $this->battle->status,
            'challengerVotes' => $this->battle->challenger_votes,
            'challengedVotes' => $this->battle->challenged_votes,
            'timeRemaining' => $this->battle->battle_end_time ? $this->battle->battle_end_time->diffInSeconds(now()) : null,
            'winnerId' => $this->battle->winner_id,
        ];
    }
}
