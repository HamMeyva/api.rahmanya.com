<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use App\Models\PKRound;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PKBattleGoalScored Event (v2.0)
 *
 * Fired when a goal is scored during PK battle
 * (when accumulated shoots reach the threshold)
 *
 * This event triggers the goal animation on frontend
 */
class PKBattleGoalScored implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;
    public $round;
    public $scorerId;

    /**
     * @param PKBattle $battle
     * @param PKRound $round
     * @param int $scorerId User ID who scored the goal
     */
    public function __construct(PKBattle $battle, PKRound $round, int $scorerId)
    {
        $this->battle = $battle;
        $this->round = $round;
        $this->scorerId = $scorerId;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to ALL participant streams (challenger + opponent + cohosts)
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

        // 4. Also broadcast to dedicated PK battle channel
        $channels[] = new Channel("pk-battle.{$this->battle->id}");

        \Log::info('⚽ PK Goal Scored Broadcast', [
            'battle_id' => $this->battle->id,
            'round_number' => $this->round->round_number,
            'scorer_id' => $this->scorerId,
            'goals_a' => $this->round->goals_a,
            'goals_b' => $this->round->goals_b,
            'channels_count' => count($channels),
        ]);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PKBattleGoalScored';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'pk_battle_goal_scored',
            'battle_id' => $this->battle->id,
            'round_number' => $this->round->round_number,
            'scorer_id' => $this->scorerId,
            'is_challenger' => $this->scorerId === $this->battle->challenger_id,
            'goals_a' => $this->round->goals_a,
            'goals_b' => $this->round->goals_b,
            'shoots_a' => $this->round->shoots_a, // Remaining shoots after goal
            'shoots_b' => $this->round->shoots_b,
            'ball_position' => $this->round->getBallPosition(),
            'score_a' => $this->round->score_a,
            'score_b' => $this->round->score_b,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
