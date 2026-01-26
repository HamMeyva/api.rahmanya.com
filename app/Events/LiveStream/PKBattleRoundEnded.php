<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleRoundEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;
    public $roundWinner;
    public $isBattleFinished;

    public function __construct(PKBattle $battle, $roundWinner = null, bool $isBattleFinished = false)
    {
        $this->battle = $battle;
        $this->roundWinner = $roundWinner;
        $this->isBattleFinished = $isBattleFinished;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to ALL participant streams (host + opponent + cohosts)
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // 1. Broadcast to host/challenger stream
        $channels[] = new \Illuminate\Broadcasting\Channel("live-stream.{$this->battle->live_stream_id}");

        // 2. Broadcast to opponent stream (if exists)
        if ($this->battle->opponent_stream_id) {
            $channels[] = new \Illuminate\Broadcasting\Channel("live-stream.{$this->battle->opponent_stream_id}");
        }

        // 3. Broadcast to all cohost streams (viewers not in PK)
        if (!empty($this->battle->cohost_stream_ids)) {
            foreach ($this->battle->cohost_stream_ids as $cohostStreamId) {
                $channels[] = new \Illuminate\Broadcasting\Channel("live-stream.{$cohostStreamId}");
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PKBattleRoundEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'pk_battle_round_ended',
            'battle_id' => $this->battle->id,
            'live_stream_id' => $this->battle->live_stream_id,
            'round_number' => $this->battle->current_round - 1, // Bitirilen devre
            'round_winner' => $this->roundWinner,
            'challenger_goals' => $this->battle->challenger_goals,
            'opponent_goals' => $this->battle->opponent_goals,
            'is_battle_finished' => $this->isBattleFinished,
            'battle_winner_id' => $this->battle->winner_id,
            'timestamp' => now()->toISOString(),
        ];
    }
}
