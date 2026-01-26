<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleCountdownStarted implements ShouldBroadcastNow
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
        return 'PKBattleCountdownStarted';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'pk_battle_countdown_started',
            'battle_id' => $this->battle->id,
            'live_stream_id' => $this->battle->live_stream_id,
            'countdown_duration' => $this->battle->countdown_duration,
            'countdown_started_at' => $this->battle->countdown_started_at,
            'server_time' => now()->toISOString(),
            'challenger' => [
                'id' => $this->battle->challenger_id,
                'username' => $this->battle->challenger->username ?? null,
            ],
            'opponent' => [
                'id' => $this->battle->opponent_id,
                'username' => $this->battle->opponent->username ?? null,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}