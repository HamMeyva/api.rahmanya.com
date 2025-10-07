<?php

namespace App\Jobs\Challenges;

use App\Models\Challenge\Challenge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use App\Models\Challenge\ChallengeTeam;
use App\Models\Challenge\ChallengeRound;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessChallengeRound implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $challengeId, public int $currentRound) {}

    public function handle()
    {
        Log::info("--------------------START::ProcessChallengeRound Job--------------------", [
            'challengeId' => $this->challengeId,
            'currentRound' => $this->currentRound,
        ]);

        $challenge = Challenge::find($this->challengeId);
        if (!$challenge || $challenge->status_id !== Challenge::STATUS_ACTIVE) {
            Log::info("Challenge bulunamadı veya aktif değil", [
                'challengeId' => $this->challengeId,
                'currentRound' => $this->currentRound,
            ]);
            Log::info("--------------------END::ProcessChallengeRound Job--------------------");
            return;
        }

        // Yeni round oluşması gerekiyorsa gerekli işlemleri yap
        if ($challenge->round_count > $this->currentRound) {
            $nextRound = $this->currentRound + 1;

            $challengeRound = ChallengeRound::create([
                'challenge_id' => $this->challengeId,
                'round_number' => $nextRound,
                'start_at' => now(),
                'end_at' => now()->addSeconds($challenge->round_duration),
            ]);

            // Challenge current_round güncelle
            $challenge->update(['current_round' => $nextRound]);

            // Sonraki round için yeni Job kuyruğa at

            Log::info("3--Bir sonraki round sonu jobu çalışma tarihi. Next Round:{$nextRound} " . $challengeRound->end_at->toDateTimeString());
            ProcessChallengeRound::dispatch($this->challengeId, $nextRound)
                ->delay($challengeRound->end_at);
        } else {
            // Challenge bitmiş burada gerekli olaylar yapılacak.

            //yayıncıların kazançlarını redisten dbye yaz
            $roundCount = $this->currentRound;

            $totalChallengeCoins = 0;
            for ($round = 1; $round <= $roundCount; $round++) {
                $challengeStreamerTotalCoinsKey = "challenge:{$challenge->id}:{$round}:streamer_total_coins";

                $streamerCoins = Redis::zrange($challengeStreamerTotalCoinsKey, 0, -1, ['WITHSCORES' => true]);

                $teamCoinsMap = [];
                foreach ($streamerCoins as $streamerUserId => $coins) {
                    $coins = $coins ? (int)$coins : 0;

                    $challengeTeam = ChallengeTeam::query()
                        ->where('challenge_id', $challenge->id)
                        ->where('user_id', $streamerUserId)
                        ->first();

                    if ($challengeTeam) {
                        $challengeTeam->increment('total_coins_earned', $coins);
                        $totalChallengeCoins += $coins;

                        // Bu round için takım coinlerini biriktir
                        $teamCoinsMap[$challengeTeam->team_no] = $coins;
                    }
                }

                Log::info("Round bilgilerini kaydet", [
                    'round' => $round,
                    'teamCoinsMap' => $teamCoinsMap,
                ]);
                // Round bilgilerini kaydet
                $challengeRound = ChallengeRound::where('challenge_id', $challenge->id)
                    ->where('round_number', $round)
                    ->first();
                Log::info("Round bilgileri", [
                    'round' => $challengeRound,
                ]);

                if ($challengeRound) {
                    $winnerTeamNo = null;
                    $maxCoins = 0;
                    foreach ($teamCoinsMap as $teamNo => $coins) {
                        if ($coins > $maxCoins) {
                            $maxCoins = $coins;
                            $winnerTeamNo = $teamNo;
                        }
                    }

                    $challengeRound->update([
                        'team_total_coins' => $teamCoinsMap,
                        'winner_team_no' => $winnerTeamNo,
                    ]);
                }

                Redis::del($challengeStreamerTotalCoinsKey);
            }

            $challenge->update([
                'status_id' => Challenge::STATUS_FINISHED,
                'total_coins_earned' => $totalChallengeCoins,
                'ended_at' => $challengeRound->end_at,
            ]);

            $agoraChannel = $challenge->agoraChannel;
            if ($agoraChannel) {
                $agoraChannel->update([
                    'is_challenge_active' => false,
                ]);
            }
        }

        Log::info("--------------------END::ProcessChallengeRound Job--------------------");
    }
}
