<?php

namespace App\Events\LiveStream;

use App\Models\PKBattle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PKBattleGiftReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $battle;
    public $giftData;

    public function __construct(PKBattle $battle, array $giftData)
    {
        $this->battle = $battle;
        $this->giftData = $giftData;
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

        // 🎮 DEBUG: Log broadcast channels
        $channelNames = array_map(function($channel) {
            return $channel->name;
        }, $channels);

        \Log::info('🎮 PK Gift Broadcast: Broadcasting to channels', [
            'battle_id' => $this->battle->id,
            'channels' => $channelNames,
            'live_stream_id' => $this->battle->live_stream_id,
            'opponent_stream_id' => $this->battle->opponent_stream_id,
            'cohost_stream_ids' => $this->battle->cohost_stream_ids,
            'receiver_id' => $this->giftData['receiver_id'] ?? 'unknown',
        ]);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PKBattleGiftReceived';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'pk_battle_gift_received',
            'battle_id' => $this->battle->id,
            'live_stream_id' => $this->battle->live_stream_id,
            'gift_data' => $this->giftData,
            'challenger_score' => $this->battle->challenger_score,
            'opponent_score' => $this->battle->opponent_score,
            'challenger_gift_count' => $this->battle->challenger_gift_count,
            'opponent_gift_count' => $this->battle->opponent_gift_count,
            'total_gift_value' => $this->battle->total_gift_value,
            'timestamp' => now()->toISOString(),
        ];
    }
}