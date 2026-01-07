<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleTimerSync implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;
    public $syncData;

    public function __construct(PKBattle $battle, array $syncData = [])
    {
        $this->battle = $battle;
        $this->syncData = $syncData;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("live-stream.{$this->battle->live_stream_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PKBattleTimerSync';
    }

    public function broadcastWith(): array
    {
        $countdownRemaining = 0;
        if ($this->battle->countdown_started_at && $this->battle->battle_phase === 'countdown') {
            $elapsed = now()->diffInSeconds($this->battle->countdown_started_at);
            $countdownRemaining = max(0, $this->battle->countdown_duration - $elapsed);
        }

        return [
            'type' => 'pk_battle_timer_sync',
            'battle_id' => $this->battle->id,
            'live_stream_id' => $this->battle->live_stream_id,
            'battle_phase' => $this->battle->battle_phase,
            'server_time' => now()->toISOString(),
            'countdown_remaining' => $countdownRemaining,
            'sync_data' => array_merge([
                'server_sync_time' => $this->battle->server_sync_time,
                'last_activity_at' => $this->battle->last_activity_at,
            ], $this->syncData),
            'timestamp' => now()->toISOString(),
        ];
    }
}