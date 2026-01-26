<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;

    public function __construct(PKBattle $battle)
    {
        $this->battle = $battle;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to ALL participant streams (host + opponent + cohosts)
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // 1. Broadcast to host/challenger stream
        $channels[] = new Channel("live-stream.{$this->battle->live_stream_id}");

        // 2. Broadcast to opponent stream (if exists)
        if ($this->battle->opponent_stream_id) {
            $channels[] = new Channel("live-stream.{$this->battle->opponent_stream_id}");
        }

        // 3. Broadcast to all cohost streams (viewers not in PK)
        if (!empty($this->battle->cohost_stream_ids)) {
            foreach ($this->battle->cohost_stream_ids as $cohostStreamId) {
                $channels[] = new Channel("live-stream.{$cohostStreamId}");
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PKBattleStarted';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'pk_battle_started',
            'battle_id' => $this->battle->id,
            'host_stream_id' => $this->battle->live_stream_id,
            'host_user_id' => $this->battle->challenger_id,
            'host_user_name' => $this->battle->challenger ? ($this->battle->challenger->nickname ?? $this->battle->challenger->name ?? 'Host') : 'Host',
            'cohost_stream_ids' => $this->battle->opponent_stream_id ? [$this->battle->opponent_stream_id] : [],
            'cohost_user_id' => $this->battle->opponent_id,
            'cohost_user_name' => $this->battle->opponent ? ($this->battle->opponent->nickname ?? $this->battle->opponent->name ?? 'Opponent') : 'Opponent',
            'scores' => [
                'host' => $this->battle->challenger_score,
                'cohost' => $this->battle->opponent_score,
            ],
            'current_round' => $this->battle->current_round ?? 1,
            'total_rounds' => $this->battle->total_rounds ?? 3,
            'duration_seconds' => ($this->battle->round_duration_minutes ?? 5) * 60,
            'status' => $this->battle->status,
            'started_at' => $this->battle->started_at,
            'timestamp' => now()->toISOString(),
        ];
    }
}
