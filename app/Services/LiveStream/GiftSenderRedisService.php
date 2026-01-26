<?php

namespace App\Services\LiveStream;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use App\Events\LiveStream\TopGiftSendersUpdated;
use App\Models\Agora\AgoraChannel;
use App\Models\Challenge\ChallengeTeam;
use App\Models\PKBattle;
use App\Services\LiveStream\PKBattleScoreService;
use Illuminate\Support\Facades\Log;

class GiftSenderRedisService
{

    public function handleGift(
        AgoraChannel $stream,
        string $recipientUserId,
        string $senderUserId,
        int $totalCost
    ): void {
        // Hediyeyi redise kaydet
        $redisKeyGiftSendersByStreamer = "agora_channel:{$stream->id}:streamer:{$recipientUserId}:gift_senders";
        Redis::zincrby($redisKeyGiftSendersByStreamer, (int)$totalCost, (string)$senderUserId);

        Log::info('Gift sent to streamer', [
            'stream_id' => $stream->id,
            'recipient_user_id' => $recipientUserId,
            'sender_user_id' => $senderUserId,
            'total_cost' => $totalCost,
        ]);

        // Sıralamayı yayına gönder
        $payload = $this->getTopGiftSendersByStreamer($stream->id, $recipientUserId);
        broadcast(new TopGiftSendersUpdated($stream->id, $payload));


        // PK Battle skorlarını güncelle
        $this->updatePKBattleScores($stream, $recipientUserId, $totalCost);

        //eğer pk aktifse
        $challenge = $stream->activeChallenge;
        if ($stream->is_challenge_active && $challenge) {
            $challengeId = $challenge->id;

            $redisKeyStreamerTotalCoins = "challenge:{$challengeId}:{$challenge->current_round}:streamer_total_coins";
            Redis::zincrby($redisKeyStreamerTotalCoins, $totalCost, $recipientUserId); // Alıcı (receiverUserId) bazında coin arttır


            $team = ChallengeTeam::where('challenge_id', $challenge->id)
                ->where('user_id', $recipientUserId)
                ->first();

            if ($team) {
                $redisKeyTeamTotalCoins = "challenge:{$challengeId}:{$challenge->current_round}:team_total_coins";
                Redis::zincrby($redisKeyTeamTotalCoins, $totalCost, $team->id); // Takım bazında coin arttır

                $teamCoins = Redis::zscore($redisKeyTeamTotalCoins, $team->id);

                $winKey = "challenge:{$challengeId}:{$challenge->current_round}:team_wins"; // Takım bazlı win sayısını tutan key

                $currentWins = floor($teamCoins / $challenge->max_coins); //takımın win sayısı güncellendi

                Redis::zadd($winKey, $currentWins, $team->id);
            }
        }
    }

    public function getTopGiftSendersByStreamer(string $channelId, string $streamerUserId, int $limit = 3): array
    {
        $redisKey = "agora_channel:{$channelId}:streamer:{$streamerUserId}:gift_senders";

        $topUsersWithScores = Redis::zrevrange($redisKey, 0, $limit - 1, ['withscores' => true]);
        $topUserIds = array_keys($topUsersWithScores);

        if (empty($topUserIds)) {
            return [
                'receiver_user_id' => $streamerUserId,
                'top_senders' => [],
            ];
        }

        $users = User::whereIn('id', $topUserIds)->get();

        $streamerTopSenders = $users->map(function ($user, $index) use ($topUsersWithScores) {
            return [
                'rank' => ++$index,
                'user_id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'total_amount' => (int) ($topUsersWithScores[$user->id] ?? 0),
            ];
        });

        return [
            'receiver_user_id' => $streamerUserId,
            'top_senders' => $streamerTopSenders,
        ];
    }

    /**
     * PK Battle skorlarını güncelle
     */
    private function updatePKBattleScores(AgoraChannel $stream, string $recipientUserId, int $totalCost): void
    {
        // PK Battle var mı kontrol et
        $pkBattle = PKBattle::where('live_stream_id', $stream->id)
            ->where('status', 'active')
            ->where('is_round_active', true)
            ->first();

        if (!$pkBattle) {
            return; // PK Battle yok veya aktif değil
        }

        // Hediye alan kişi battle katılımcısı mı kontrol et
        if ($recipientUserId !== $pkBattle->challenger_id && $recipientUserId !== $pkBattle->opponent_id) {
            return; // Hediye battle katılımcısına gönderilmemiş
        }

        try {
            // PK Battle skorlarını hesapla ve yayınla
            $scoreService = app(PKBattleScoreService::class);
            $scoreService->calculateAndUpdateScores($pkBattle);
            
            Log::info('PK Battle scores updated after gift', [
                'battle_id' => $pkBattle->id,
                'recipient_user_id' => $recipientUserId,
                'gift_value' => $totalCost,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update PK Battle scores after gift', [
                'battle_id' => $pkBattle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resetTopSenders(int $channelId): void
    {
        //yayın bitince redis keylerini silecegiz

        //$redisKey = "agora_channel:{$channelId}:top_gift_senders";
        //Redis::del($redisKey);
    }
}
