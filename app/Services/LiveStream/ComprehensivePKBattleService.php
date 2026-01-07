<?php

namespace App\Services\LiveStream;

use App\Models\PKBattle;
use App\Models\PKBattleScore;
use App\Models\Agora\AgoraChannel;
use App\Events\LiveStream\PKBattleStarted;
use App\Events\LiveStream\PKBattleEnded;
use App\Events\LiveStream\PKBattleScoreUpdated;
use App\Jobs\PKBattleAutoEndJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Comprehensive PK Battle Service
 * Handles complete battle lifecycle: start, process gifts, calculate scores, end battles
 */
class ComprehensivePKBattleService
{
    protected PKBattleScoreService $scoreService;

    public function __construct(PKBattleScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Start a new PK battle between two streamers
     *
     * @param string $hostStreamId Challenger/Host stream ID
     * @param string $opponentUserId Opponent user ID
     * @param string|null $opponentStreamId Optional opponent stream ID
     * @param int $durationSeconds Battle duration in seconds (default: 300 = 5 minutes)
     * @return array
     */
    public function startPKBattle(
        string $hostStreamId,
        string $opponentUserId,
        ?string $opponentStreamId = null,
        int $durationSeconds = 300
    ): array {
        try {
            DB::beginTransaction();

            // Validate host stream exists and is live
            $hostStream = AgoraChannel::find($hostStreamId);
            if (!$hostStream) {
                throw new \Exception('Host stream not found');
            }

            if (!$hostStream->is_online) {
                throw new \Exception('Host stream is not live');
            }

            // Check for existing active PK battle on this stream
            $existingBattle = PKBattle::where('live_stream_id', $hostStreamId)
                ->where('status', 'ACTIVE')
                ->first();

            if ($existingBattle) {
                throw new \Exception('This stream already has an active PK battle');
            }

            // Get opponent stream ID if not provided
            if (!$opponentStreamId) {
                $opponentStream = AgoraChannel::where('user_id', $opponentUserId)
                    ->where('status_id', AgoraChannel::STATUS_LIVE)
                    ->where('is_online', true)
                    ->first();

                if ($opponentStream) {
                    $opponentStreamId = (string) $opponentStream->_id;
                }
            }

            // Get cohost stream IDs
            $cohostStreamIds = [];
            if (!empty($hostStream->cohost_channel_ids)) {
                $cohostChannels = AgoraChannel::whereIn('_id', $hostStream->cohost_channel_ids)
                    ->where('status_id', AgoraChannel::STATUS_LIVE)
                    ->where('is_online', true)
                    ->get()
                    ->map(fn($channel) => (string) $channel->_id)
                    ->toArray();

                $cohostStreamIds = $cohostChannels;
            }

            // Calculate end time
            $startTime = now();
            $endTime = $startTime->copy()->addSeconds($durationSeconds);

            // Create PK battle
            $battle = PKBattle::create([
                'live_stream_id' => $hostStreamId,
                'battle_id' => 'pk_' . uniqid(),
                'challenger_id' => $hostStream->user_id,
                'opponent_id' => $opponentUserId,
                'opponent_stream_id' => $opponentStreamId,
                'cohost_stream_ids' => $cohostStreamIds,
                'status' => 'PENDING',
                'battle_phase' => 'COUNTDOWN',
                'countdown_duration' => 10,
                'duration_seconds' => $durationSeconds,
                'challenger_score' => 0,
                'opponent_score' => 0,
                'challenger_gift_count' => 0,
                'opponent_gift_count' => 0,
                'total_gift_value' => 0,
                'challenger_stream_status' => 'DISCONNECTED',
                'opponent_stream_status' => 'DISCONNECTED',
                'started_at' => $startTime,
                'battle_config' => [
                    'duration_seconds' => $durationSeconds,
                    'end_time' => $endTime->toISOString(),
                    'created_by' => $hostStream->user_id,
                    'opponent_stream_found' => !is_null($opponentStreamId),
                    'cohost_count' => count($cohostStreamIds),
                ],
            ]);

            // Update stream mode
            $hostStream->update(['mode' => 'pk_battle']);

            // Schedule auto-end job
            PKBattleAutoEndJob::dispatch($battle->id)
                ->delay($endTime);

            DB::commit();

            // Cache battle for quick access
            $this->cacheBattle($battle);

            Log::info('PK Battle started', [
                'battle_id' => $battle->id,
                'host_stream_id' => $hostStreamId,
                'opponent_stream_id' => $opponentStreamId,
                'duration_seconds' => $durationSeconds,
            ]);

            return [
                'success' => true,
                'message' => 'PK Battle created successfully',
                'battle' => $battle->fresh(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to start PK Battle', [
                'error' => $e->getMessage(),
                'host_stream_id' => $hostStreamId,
                'opponent_user_id' => $opponentUserId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
            ];
        }
    }

    /**
     * Accept PK battle invitation
     *
     * @param int $battleId
     * @param string $userId User accepting the battle
     * @return array
     */
    public function acceptPKBattle(int $battleId, string $userId): array
    {
        try {
            DB::beginTransaction();

            $battle = PKBattle::findOrFail($battleId);

            // Normalize UUIDs for comparison
            $normalizedOpponentId = str_replace('-', '', $battle->opponent_id);
            $normalizedUserId = str_replace('-', '', $userId);

            if ($normalizedOpponentId !== $normalizedUserId) {
                throw new \Exception('Only the invited opponent can accept this battle');
            }

            if ($battle->status !== 'PENDING') {
                throw new \Exception('This battle invitation is no longer valid');
            }

            // Update battle to active
            $battle->update([
                'status' => 'ACTIVE',
                'battle_phase' => 'COUNTDOWN',
                'countdown_started_at' => now(),
                'server_sync_time' => now(),
                'last_activity_at' => now(),
            ]);

            DB::commit();

            // Broadcast battle started event
            broadcast(new PKBattleStarted($battle->fresh()));

            // Update cache
            $this->cacheBattle($battle);

            Log::info('PK Battle accepted', [
                'battle_id' => $battle->id,
                'accepted_by' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'PK Battle accepted successfully',
                'battle' => $battle->fresh(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to accept PK Battle', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
            ];
        }
    }

    /**
     * Process gift sent during PK battle
     *
     * @param int $battleId
     * @param string $fromUserId Sender user ID
     * @param string $toStreamerId Recipient streamer ID
     * @param int $giftId
     * @param int $giftValue Coin value of the gift
     * @param int $quantity
     * @param int|null $transactionId Gift transaction ID
     * @return array
     */
    public function processGift(
        int $battleId,
        string $fromUserId,
        string $toStreamerId,
        int $giftId,
        int $giftValue,
        int $quantity = 1,
        ?int $transactionId = null
    ): array {
        try {
            DB::beginTransaction();

            $battle = PKBattle::findOrFail($battleId);

            // Validate battle is active
            if ($battle->status !== 'ACTIVE') {
                throw new \Exception('Battle is not active');
            }

            // Validate recipient is a battle participant
            if ($toStreamerId !== $battle->challenger_id && $toStreamerId !== $battle->opponent_id) {
                throw new \Exception('Recipient is not a battle participant');
            }

            // Calculate total value
            $totalValue = $giftValue * $quantity;

            // Record gift in pk_battle_scores
            PKBattleScore::create([
                'pk_battle_id' => $battleId,
                'user_id' => $fromUserId,
                'streamer_id' => $toStreamerId,
                'gift_id' => $giftId,
                'gift_value' => $giftValue,
                'quantity' => $quantity,
                'total_value' => $totalValue,
                'gift_transaction_id' => $transactionId,
            ]);

            // Update battle scores
            $challengerScore = PKBattleScore::getStreamerScore($battleId, $battle->challenger_id);
            $opponentScore = PKBattleScore::getStreamerScore($battleId, $battle->opponent_id);

            // Calculate gift counts
            $challengerGiftCount = PKBattleScore::where('pk_battle_id', $battleId)
                ->where('streamer_id', $battle->challenger_id)
                ->sum('quantity');

            $opponentGiftCount = PKBattleScore::where('pk_battle_id', $battleId)
                ->where('streamer_id', $battle->opponent_id)
                ->sum('quantity');

            // Update battle
            $battle->update([
                'challenger_score' => $challengerScore,
                'opponent_score' => $opponentScore,
                'challenger_gift_count' => $challengerGiftCount,
                'opponent_gift_count' => $opponentGiftCount,
                'total_gift_value' => $challengerScore + $opponentScore,
                'last_activity_at' => now(),
            ]);

            DB::commit();

            // Calculate ball position
            $ballPosition = $this->calculateBallPosition($challengerScore, $opponentScore);

            // Prepare score update data
            $scoreUpdate = [
                'battle_id' => $battle->id,
                'challenger_score' => $challengerScore,
                'opponent_score' => $opponentScore,
                'ball_position' => $ballPosition,
                'leader' => $this->determineLeader($challengerScore, $opponentScore),
                'last_gift' => [
                    'from_user_id' => $fromUserId,
                    'to_streamer_id' => $toStreamerId,
                    'gift_id' => $giftId,
                    'gift_value' => $giftValue,
                    'quantity' => $quantity,
                    'total_value' => $totalValue,
                ],
                'timestamp' => now()->toISOString(),
            ];

            // Broadcast score update
            broadcast(new PKBattleScoreUpdated(
                $battle->live_stream_id,
                $scoreUpdate,
                $battle->opponent_stream_id,
                $battle->cohost_stream_ids ?? []
            ));

            // Update cache
            $this->cacheBattle($battle);

            Log::info('PK Battle gift processed', [
                'battle_id' => $battleId,
                'from_user' => $fromUserId,
                'to_streamer' => $toStreamerId,
                'gift_value' => $giftValue,
                'quantity' => $quantity,
                'new_scores' => [
                    'challenger' => $challengerScore,
                    'opponent' => $opponentScore,
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Gift processed successfully',
                'score_update' => $scoreUpdate,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process PK Battle gift', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
                'from_user' => $fromUserId,
                'to_streamer' => $toStreamerId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'score_update' => null,
            ];
        }
    }

    /**
     * End PK battle and determine winner
     *
     * @param int $battleId
     * @param bool $isAutoEnd Whether this is auto-ended by job
     * @return array
     */
    public function endPKBattle(int $battleId, bool $isAutoEnd = false): array
    {
        try {
            DB::beginTransaction();

            $battle = PKBattle::findOrFail($battleId);

            if ($battle->status === 'FINISHED') {
                return [
                    'success' => true,
                    'message' => 'Battle already finished',
                    'battle' => $battle,
                ];
            }

            // Determine winner
            $winnerId = null;
            if ($battle->challenger_score > $battle->opponent_score) {
                $winnerId = $battle->challenger_id;
            } elseif ($battle->opponent_score > $battle->challenger_score) {
                $winnerId = $battle->opponent_id;
            }

            // Update battle status
            $battle->update([
                'status' => 'FINISHED',
                'battle_phase' => 'ENDED',
                'ended_at' => now(),
                'winner_id' => $winnerId,
            ]);

            // Reset stream mode
            $hostStream = AgoraChannel::find($battle->live_stream_id);
            if ($hostStream) {
                $hostStream->update(['mode' => 'normal']);
            }

            DB::commit();

            // Broadcast battle ended event
            broadcast(new PKBattleEnded($battle->fresh()));

            // Clear cache
            $this->clearBattleCache($battleId);

            Log::info('PK Battle ended', [
                'battle_id' => $battleId,
                'winner_id' => $winnerId,
                'final_scores' => [
                    'challenger' => $battle->challenger_score,
                    'opponent' => $battle->opponent_score,
                ],
                'is_auto_end' => $isAutoEnd,
            ]);

            return [
                'success' => true,
                'message' => 'PK Battle ended successfully',
                'battle' => $battle->fresh(),
                'winner_id' => $winnerId,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to end PK Battle', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
                'winner_id' => null,
            ];
        }
    }

    /**
     * Get active PK battle for a stream
     *
     * @param string $streamId
     * @return PKBattle|null
     */
    public function getActiveBattle(string $streamId): ?PKBattle
    {
        // Try cache first
        $cacheKey = "pk_battle:stream:{$streamId}";
        $battleId = Cache::get($cacheKey);

        if ($battleId) {
            return PKBattle::find($battleId);
        }

        // Query database
        $battle = PKBattle::where('status', 'ACTIVE')
            ->where(function ($query) use ($streamId) {
                $query->where('live_stream_id', $streamId)
                    ->orWhere('opponent_stream_id', $streamId)
                    ->orWhereJsonContains('cohost_stream_ids', $streamId);
            })
            ->first();

        if ($battle) {
            $this->cacheBattle($battle);
        }

        return $battle;
    }

    /**
     * Calculate ball position based on score differential
     * Returns value from -1.0 to 1.0
     *
     * @param int $challengerScore
     * @param int $opponentScore
     * @return float
     */
    protected function calculateBallPosition(int $challengerScore, int $opponentScore): float
    {
        $totalScore = $challengerScore + $opponentScore;

        if ($totalScore === 0) {
            return 0.0; // Neutral position
        }

        $scoreDiff = $challengerScore - $opponentScore;

        // Ball position ranges from -1.0 (opponent winning) to 1.0 (challenger winning)
        return round($scoreDiff / ($totalScore + 1), 2);
    }

    /**
     * Determine current leader
     *
     * @param int $challengerScore
     * @param int $opponentScore
     * @return string 'challenger', 'opponent', or 'tie'
     */
    protected function determineLeader(int $challengerScore, int $opponentScore): string
    {
        if ($challengerScore > $opponentScore) {
            return 'challenger';
        } elseif ($opponentScore > $challengerScore) {
            return 'opponent';
        }

        return 'tie';
    }

    /**
     * Cache battle for quick access
     *
     * @param PKBattle $battle
     */
    protected function cacheBattle(PKBattle $battle): void
    {
        $ttl = 3600; // 1 hour

        // Cache by battle ID
        Cache::put("pk_battle:{$battle->id}", $battle, $ttl);

        // Cache by stream IDs for quick lookup
        Cache::put("pk_battle:stream:{$battle->live_stream_id}", $battle->id, $ttl);

        if ($battle->opponent_stream_id) {
            Cache::put("pk_battle:stream:{$battle->opponent_stream_id}", $battle->id, $ttl);
        }

        foreach ($battle->cohost_stream_ids ?? [] as $cohostStreamId) {
            Cache::put("pk_battle:stream:{$cohostStreamId}", $battle->id, $ttl);
        }
    }

    /**
     * Clear battle cache
     *
     * @param int $battleId
     */
    protected function clearBattleCache(int $battleId): void
    {
        $battle = PKBattle::find($battleId);

        if (!$battle) {
            return;
        }

        Cache::forget("pk_battle:{$battle->id}");
        Cache::forget("pk_battle:stream:{$battle->live_stream_id}");

        if ($battle->opponent_stream_id) {
            Cache::forget("pk_battle:stream:{$battle->opponent_stream_id}");
        }

        foreach ($battle->cohost_stream_ids ?? [] as $cohostStreamId) {
            Cache::forget("pk_battle:stream:{$cohostStreamId}");
        }
    }

    /**
     * Get PK battle statistics
     *
     * @param int $battleId
     * @return array
     */
    public function getBattleStats(int $battleId): array
    {
        $battle = PKBattle::findOrFail($battleId);

        $stats = PKBattleScore::getBattleStats($battleId);

        $challengerTopSenders = PKBattleScore::getTopSenders($battleId, $battle->challenger_id, 10);
        $opponentTopSenders = PKBattleScore::getTopSenders($battleId, $battle->opponent_id, 10);

        return [
            'battle_id' => $battleId,
            'status' => $battle->status,
            'challenger_score' => $battle->challenger_score,
            'opponent_score' => $battle->opponent_score,
            'ball_position' => $this->calculateBallPosition($battle->challenger_score, $battle->opponent_score),
            'leader' => $this->determineLeader($battle->challenger_score, $battle->opponent_score),
            'winner_id' => $battle->winner_id,
            'total_gifts' => $stats['total_gifts'],
            'total_value' => $stats['total_value'],
            'unique_senders' => $stats['unique_senders'],
            'challenger_top_senders' => $challengerTopSenders,
            'opponent_top_senders' => $opponentTopSenders,
            'started_at' => $battle->started_at,
            'ended_at' => $battle->ended_at,
        ];
    }
}
