<?php

namespace App\Services\LiveStream;

use App\Models\PKBattle;
use App\Models\User;
use App\Models\Agora\AgoraChannelGift;
use App\Events\LiveStream\PKBattleScoreUpdated;
use App\Events\LiveStream\PKBattleRoundEnded;
use App\Events\LiveStream\PKBattleEnded;
use App\Events\LiveStream\PKBattleGiftReceived;
use App\Models\PKBattleStateLog;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PKBattleScoreService
{
    /**
     * PK Battle skorlarını hesapla ve güncelle
     */
    public function calculateAndUpdateScores(PKBattle $battle): array
    {
        $challenger = User::find($battle->challenger_id);
        $opponent = User::find($battle->opponent_id);

        if (!$challenger || !$opponent) {
            throw new \Exception('Battle participants not found');
        }

        // Mevcut devre için hediye skorlarını hesapla
        $challengerScore = $this->calculateUserRoundScore($battle, $battle->challenger_id);
        $opponentScore = $this->calculateUserRoundScore($battle, $battle->opponent_id);

        // Battle skorlarını güncelle
        $battle->update([
            'challenger_score' => $challengerScore['round_score'],
            'opponent_score' => $opponentScore['round_score'],
            'challenger_gift_count' => $challengerScore['gifts_count'],
            'opponent_gift_count' => $opponentScore['gifts_count'],
            'total_gift_value' => $challengerScore['total_value'] + $opponentScore['total_value'],
            'last_activity_at' => now(),
        ]);

        $scores = [
            'battle_id' => $battle->id,
            'challenger_score' => [
                'user_id' => $challenger->id,
                'user' => $challenger,
                'current_score' => $challengerScore['round_score'],
                'round_score' => $challengerScore['round_score'],
                'total_gifts_value' => $challengerScore['total_value'],
                'gifts_count' => $challengerScore['gifts_count'],
                'rank' => $challengerScore['round_score'] >= $opponentScore['round_score'] ? 1 : 2,
            ],
            'opponent_score' => [
                'user_id' => $opponent->id,
                'user' => $opponent,
                'current_score' => $opponentScore['round_score'],
                'round_score' => $opponentScore['round_score'],
                'total_gifts_value' => $opponentScore['total_value'],
                'gifts_count' => $opponentScore['gifts_count'],
                'rank' => $opponentScore['round_score'] > $challengerScore['round_score'] ? 1 : 2,
            ],
            'winner_user_id' => $this->determineRoundWinner($challengerScore['round_score'], $opponentScore['round_score']),
            'status' => $battle->status,
            'current_round' => $battle->current_round,
            'total_rounds' => $battle->total_rounds,
            'round_duration_seconds' => $battle->round_duration_minutes * 60,
            'round_started_at' => $battle->round_started_at,
            'round_ends_at' => $battle->round_ends_at,
        ];

        // Real-time update gönder - TÜM stream'lere (host + opponent + cohosts)
        broadcast(new PKBattleScoreUpdated(
            $battle->live_stream_id,
            $scores,
            $battle->opponent_stream_id, // ✅ Opponent stream'e de gönder
            $battle->cohost_stream_ids ?? [] // ✅ Tüm cohost stream'lere de gönder
        ));

        return $scores;
    }

    /**
     * Kullanıcının mevcut devre için SHOOT skorunu hesapla
     */
    private function calculateUserRoundScore(PKBattle $battle, string $userId): array
    {
        // ✅ TÜM PK battle stream'lerinden hediyeleri al (host + opponent + cohosts)
        $streamIds = array_filter([
            $battle->live_stream_id,
            $battle->opponent_stream_id,
            ...($battle->cohost_stream_ids ?? [])
        ]);

        Log::info('🎁 PK SCORE SERVICE: Querying gifts for user', [
            'user_id' => $userId,
            'battle_id' => $battle->id,
            'stream_ids' => $streamIds,
            'host_stream' => $battle->live_stream_id,
            'opponent_stream' => $battle->opponent_stream_id,
            'cohost_streams' => $battle->cohost_stream_ids ?? [],
        ]);

        $gifts = AgoraChannelGift::whereIn('agora_channel_id', $streamIds)
            ->where('recipient_user_id', $userId)
            ->where('created_at', '>=', $battle->round_started_at ?? $battle->started_at)
            ->where('created_at', '<=', $battle->round_ends_at ?? now())
            ->get();

        $totalValue = 0;
        $giftsCount = 0;

        foreach ($gifts as $gift) {
            $giftValue = $gift->coin_value ?? 0;
            $quantity = $gift->quantity ?? 1;

            // Hediye değerini SHOOT'a çevir (hediye fiyatının yarısı = SHOOT puanı)
            $shootValue = ($giftValue / 2) * $quantity;
            $totalValue += $shootValue;
            $giftsCount += $quantity;

            Log::info('🎁 PK SCORE SERVICE: Processing gift', [
                'gift_id' => $gift->id,
                'agora_channel_id' => $gift->agora_channel_id,
                'recipient_user_id' => $gift->recipient_user_id,
                'coin_value' => $giftValue,
                'quantity' => $quantity,
                'shoot_value' => $shootValue,
            ]);
        }

        Log::info('🎁 PK SCORE SERVICE: Calculated round score', [
            'user_id' => $userId,
            'battle_id' => $battle->id,
            'round_start' => $battle->round_started_at,
            'round_end' => $battle->round_ends_at,
            'gifts_found_count' => $gifts->count(),
            'total_value' => $totalValue,
            'gifts_count' => $giftsCount,
        ]);

        return [
            'round_score' => $totalValue,
            'total_value' => $totalValue,
            'gifts_count' => $giftsCount,
        ];
    }

    /**
     * Devre kazananını belirle
     */
    private function determineRoundWinner(int $challengerScore, int $opponentScore): ?string
    {
        if ($challengerScore > $opponentScore) {
            return 'challenger';
        } elseif ($opponentScore > $challengerScore) {
            return 'opponent';
        }

        return null; // Berabere
    }

    /**
     * PK Battle devresini sonlandır
     */
    public function endPKBattleRound(PKBattle $battle, bool $forceEnd = false): array
    {
        if (!$battle->is_round_active && !$forceEnd) {
            throw new \Exception('No active round to end');
        }

        // Son skorları hesapla
        $scores = $this->calculateAndUpdateScores($battle);

        $challengerScore = $scores['challenger_score']['round_score'];
        $opponentScore = $scores['opponent_score']['round_score'];

        // Devre kazananını belirle
        $roundWinner = null;
        if ($challengerScore > $opponentScore) {
            $battle->increment('challenger_goals');
            $roundWinner = $battle->challenger_id;
        } elseif ($opponentScore > $challengerScore) {
            $battle->increment('opponent_goals');
            $roundWinner = $battle->opponent_id;
        }

        // Devre skorunu kaydet
        $roundScores = $battle->round_scores ?? [];
        $roundScores[] = [
            'round' => $battle->current_round,
            'challenger' => $challengerScore,
            'opponent' => $opponentScore,
            'winner' => $roundWinner,
            'ended_at' => now()->toISOString(),
        ];

        $battle->update([
            'round_scores' => $roundScores,
            'is_round_active' => false,
        ]);

        // Battle bitmiş mi kontrol et
        $isBattleFinished = false;
        $battleWinnerId = null;

        $totalRounds = $battle->total_rounds;
        $neededGoals = ceil($totalRounds / 2); // Çoğunluğu kazanan wins

        if ($battle->challenger_goals >= $neededGoals) {
            $isBattleFinished = true;
            $battleWinnerId = $battle->challenger_id;
        } elseif ($battle->opponent_goals >= $neededGoals) {
            $isBattleFinished = true;
            $battleWinnerId = $battle->opponent_id;
        } elseif ($battle->current_round >= $totalRounds) {
            // Tüm devreler bitti
            $isBattleFinished = true;
            if ($battle->challenger_goals > $battle->opponent_goals) {
                $battleWinnerId = $battle->challenger_id;
            } elseif ($battle->opponent_goals > $battle->challenger_goals) {
                $battleWinnerId = $battle->opponent_id;
            }
            // Berabere ise battleWinnerId null kalır
        }

        if ($isBattleFinished) {
            $battle->update([
                'status' => 'finished',
                'winner_id' => $battleWinnerId,
                'ended_at' => now(),
            ]);

            broadcast(new PKBattleEnded($battle));
        } else {
            // Sonraki devreye geç
            $this->startNextRound($battle);
        }

        // Devre sonu eventi
        broadcast(new PKBattleRoundEnded($battle, $roundWinner, $isBattleFinished));

        return [
            'success' => true,
            'message' => $isBattleFinished ? 'Battle finished!' : 'Round ended!',
            'winner_user_id' => $roundWinner,
            'challenger_score' => $challengerScore,
            'opponent_score' => $opponentScore,
            'is_battle_finished' => $isBattleFinished,
            'battle_winner_id' => $battleWinnerId,
        ];
    }

    /**
     * Sonraki devreyi başlat
     */
    private function startNextRound(PKBattle $battle): void
    {
        $nextRound = $battle->current_round + 1;
        $roundStartTime = now();
        $roundEndTime = $roundStartTime->addMinutes($battle->round_duration_minutes);

        $battle->update([
            'current_round' => $nextRound,
            'round_started_at' => $roundStartTime,
            'round_ends_at' => $roundEndTime,
            'is_round_active' => true,
            'challenger_score' => 0, // Yeni devre için sıfırla
            'opponent_score' => 0,
        ]);

        Log::info("PK Battle Round Started", [
            'battle_id' => $battle->id,
            'round' => $nextRound,
            'duration_minutes' => $battle->round_duration_minutes,
        ]);
    }

    /**
     * PK Battle'ın hediye istatistiklerini al
     */
    public function getPKBattleGiftStats(string $battleId): array
    {
        $battle = PKBattle::findOrFail($battleId);

        $challenger = User::find($battle->challenger_id);
        $opponent = User::find($battle->opponent_id);

        $challengerStats = $this->calculateUserRoundScore($battle, $battle->challenger_id);
        $opponentStats = $this->calculateUserRoundScore($battle, $battle->opponent_id);

        $userScores = [
            [
                'user_id' => $challenger->id,
                'user' => $challenger,
                'current_score' => $challengerStats['round_score'],
                'round_score' => $challengerStats['round_score'],
                'total_gifts_value' => $challengerStats['total_value'],
                'gifts_count' => $challengerStats['gifts_count'],
                'rank' => $challengerStats['round_score'] >= $opponentStats['round_score'] ? 1 : 2,
            ],
            [
                'user_id' => $opponent->id,
                'user' => $opponent,
                'current_score' => $opponentStats['round_score'],
                'round_score' => $opponentStats['round_score'],
                'total_gifts_value' => $opponentStats['total_value'],
                'gifts_count' => $opponentStats['gifts_count'],
                'rank' => $opponentStats['round_score'] > $challengerStats['round_score'] ? 1 : 2,
            ],
        ];

        return [
            'success' => true,
            'battle_id' => $battleId,
            'user_scores' => $userScores,
            'last_updated_at' => now(),
        ];
    }

    /**
     * Handle gift received during PK battle
     */
    public function handleGiftReceived(PKBattle $battle, array $giftData): void
    {
        // Update battle activity
        $battle->update(['last_activity_at' => now()]);

        // Log gift event
        PKBattleStateLog::logEvent(
            $battle->id,
            'gift_received',
            $giftData,
            $giftData['sender_id'] ?? null
        );

        // Recalculate scores
        $this->calculateAndUpdateScores($battle);

        // Broadcast gift received event
        broadcast(new PKBattleGiftReceived($battle, $giftData));
    }

    /**
     * Handle battle error and recovery
     */
    public function handleBattleError(PKBattle $battle, string $errorType, array $errorData, string $userId = null): void
    {
        // Update error logs
        $errorLogs = $battle->error_logs ?? [];
        $errorLogs[] = [
            'error_type' => $errorType,
            'error_data' => $errorData,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ];

        $battle->update([
            'error_logs' => $errorLogs,
            'last_activity_at' => now(),
        ]);

        // Log error event
        PKBattleStateLog::logEvent(
            $battle->id,
            'error_occurred',
            [
                'error_type' => $errorType,
                'error_data' => $errorData,
            ],
            $userId
        );

        // Implement recovery logic based on error type
        $this->attemptErrorRecovery($battle, $errorType, $errorData);
    }

    /**
     * Attempt to recover from battle errors
     */
    private function attemptErrorRecovery(PKBattle $battle, string $errorType, array $errorData): void
    {
        switch ($errorType) {
            case 'timer_stuck':
                // Reset timer synchronization
                $battle->update([
                    'server_sync_time' => now(),
                    'battle_phase' => 'active',
                ]);
                break;

            case 'stream_failure':
                // Mark streams for reconnection
                if (isset($errorData['user_id'])) {
                    $userId = $errorData['user_id'];
                    if ($userId === $battle->challenger_id) {
                        $battle->update(['challenger_stream_status' => 'reconnecting']);
                    } elseif ($userId === $battle->opponent_id) {
                        $battle->update(['opponent_stream_status' => 'reconnecting']);
                    }
                }
                break;

            case 'score_sync_failure':
                // Force score recalculation
                $this->calculateAndUpdateScores($battle);
                break;
        }
    }
}
