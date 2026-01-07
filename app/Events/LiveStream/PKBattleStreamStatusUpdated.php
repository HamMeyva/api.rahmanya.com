<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleStreamStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;
    public $userId;
    public $streamStatus;
    public $errorData;

    public function __construct(PKBattle $battle, string $userId, string $streamStatus, array $errorData = null)
    {
        $this->battle = $battle;
        $this->userId = $userId;
        $this->streamStatus = $streamStatus;
        $this->errorData = $errorData;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("live-stream.{$this->battle->live_stream_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PKBattleStreamStatusUpdated';
    }

    public function broadcastWith(): array
    {
        $userRole = 'viewer';
        if ($this->userId === $this->battle->challenger_id) {
            $userRole = 'challenger';
        } elseif ($this->userId === $this->battle->opponent_id) {
            $userRole = 'opponent';
        }

        return [
            'type' => 'pk_battle_stream_status_updated',
            'battle_id' => $this->battle->id,
            'live_stream_id' => $this->battle->live_stream_id,
            'user_id' => $this->userId,
            'user_role' => $userRole,
            'stream_status' => $this->streamStatus,
            'challenger_stream_status' => $this->battle->challenger_stream_status,
            'opponent_stream_status' => $this->battle->opponent_stream_status,
            'error_data' => $this->errorData,
            'timestamp' => now()->toISOString(),
        ];
    }
}