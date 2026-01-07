<?php

namespace App\Services\LiveStream;

use App\Models\PKBattle;
use App\Models\Agora\AgoraChannel;

class PKBattleService
{
    /**
     * Get opponent's active stream ID by user ID
     *
     * @param string $opponentUserId
     * @return string|null
     */
    protected function getOpponentStreamId(string $opponentUserId): ?string
    {
        // Find opponent's active stream
        $opponentStream = AgoraChannel::where('user_id', $opponentUserId)
            ->where('status_id', AgoraChannel::STATUS_LIVE)
            ->where('is_online', true)
            ->first();

        return $opponentStream?->id;
    }

    /**
     * Get all cohost stream IDs for a battle (cohosts not participating in PK)
     *
     * @param AgoraChannel $hostChannel
     * @return array
     */
    protected function getCohostStreamIds(AgoraChannel $hostChannel): array
    {
        $cohostStreamIds = [];

        // Get cohost channel IDs from host channel
        if (!empty($hostChannel->cohost_channel_ids)) {
            foreach ($hostChannel->cohost_channel_ids as $cohostChannelId) {
                $cohostChannel = AgoraChannel::find($cohostChannelId);
                if ($cohostChannel && $cohostChannel->is_online) {
                    $cohostStreamIds[] = $cohostChannel->id;
                }
            }
        }

        return $cohostStreamIds;
    }

    /**
     * Start a PK battle with multi-stream broadcasting support
     *
     * @param string $hostStreamId Host/challenger stream ID
     * @param string $opponentUserId Opponent user ID
     * @param int $durationSeconds Battle duration
     * @return PKBattle
     */
    public function startPKBattle(string $hostStreamId, string $opponentUserId, int $durationSeconds = 300): PKBattle
    {
        $channel = AgoraChannel::findOrFail($hostStreamId);

        if (($channel->mode ?? 'normal') === 'pk_battle') {
            throw new \RuntimeException('Aktif PK bulunuyor');
        }

        // Get opponent's stream ID
        $opponentStreamId = $this->getOpponentStreamId($opponentUserId);

        // Get additional cohost stream IDs (cohosts not in PK)
        $cohostStreamIds = $this->getCohostStreamIds($channel);

        $battle = PKBattle::create([
            'live_stream_id' => $channel->id,
            'challenger_id' => $channel->user_id,
            'opponent_id' => $opponentUserId,
            'opponent_stream_id' => $opponentStreamId,  // NEW: For broadcasting
            'cohost_stream_ids' => $cohostStreamIds,    // NEW: For broadcasting to viewers
            'status' => 'pending',
            'duration_seconds' => $durationSeconds,
            'battle_id' => 'pk_' . uniqid(),
        ]);

        $channel->update(['mode' => 'pk_battle']);

        return $battle;
    }

    public function accept(string $battleId): PKBattle
    {
        $battle = PKBattle::findOrFail($battleId);
        if ($battle->status !== 'pending') {
            throw new \RuntimeException('Geçersiz durum');
        }
        $battle->update(['status' => 'active', 'started_at' => now()]);
        return $battle->fresh();
    }

    public function end(string $battleId): PKBattle
    {
        $battle = PKBattle::findOrFail($battleId);
        $winnerId = null;
        if ($battle->challenger_score > $battle->opponent_score) {
            $winnerId = $battle->challenger_id;
        } elseif ($battle->opponent_score > $battle->challenger_score) {
            $winnerId = $battle->opponent_id;
        }
        $battle->update([
            'status' => 'finished',
            'ended_at' => now(),
            'winner_id' => $winnerId,
        ]);

        $battle->liveStream->update(['mode' => 'normal']);
        return $battle->fresh();
    }
}


