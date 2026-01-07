<?php

namespace App\Events\LiveStream;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveStreamId;           // Host/Challenger stream ID
    public $opponentStreamId;       // Opponent stream ID (nullable)
    public $cohostStreamIds;        // Additional cohost stream IDs (array)
    public $scores;

    /**
     * Create a new event instance.
     *
     * @param string $liveStreamId Host/challenger stream ID
     * @param array $scores Score data
     * @param string|null $opponentStreamId Opponent's stream ID
     * @param array $cohostStreamIds Additional cohost stream IDs
     */
    public function __construct($liveStreamId, array $scores, ?string $opponentStreamId = null, array $cohostStreamIds = [])
    {
        $this->liveStreamId = $liveStreamId;
        $this->scores = $scores;
        $this->opponentStreamId = $opponentStreamId;
        $this->cohostStreamIds = $cohostStreamIds;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to ALL participant streams (host + opponent + cohosts)
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // 1. Broadcast to host/challenger stream
        $channels[] = new Channel("live-stream.{$this->liveStreamId}");

        // 2. Broadcast to opponent stream (if exists)
        if ($this->opponentStreamId) {
            $channels[] = new Channel("live-stream.{$this->opponentStreamId}");
        }

        // 3. Broadcast to all cohost streams (viewers not in PK)
        foreach ($this->cohostStreamIds as $cohostId) {
            $channels[] = new Channel("live-stream.{$cohostId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PKBattleScoreUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'pk_battle_score_update',
            'live_stream_id' => $this->liveStreamId,
            'opponent_stream_id' => $this->opponentStreamId,
            'cohost_stream_ids' => $this->cohostStreamIds,
            'scores' => $this->scores,
            'timestamp' => now()->toISOString(),
        ];
    }
}
