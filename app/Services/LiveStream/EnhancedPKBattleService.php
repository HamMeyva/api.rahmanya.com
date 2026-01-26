<?php

namespace App\Services\LiveStream;

use App\Models\PKBattle;
use App\Models\User;
use App\Models\LiveStream;
use App\Models\Agora\AgoraChannel;
use App\Events\LiveStream\PKBattleScoreUpdated;
use App\Events\LiveStream\PKBattleGiftReceived;
use App\Events\LiveStream\PKBattleEnded;
use App\Events\LiveStream\PKBattleRoundEnded;
use App\Events\PKBattleUpdatedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EnhancedPKBattleService
{
    /**
     * TikTok tarzı PK Battle başlatır - çoklu katılımcı destekli
     */
    public function startMultiPlayerPKBattle(array $config): array
    {
        try {
            DB::beginTransaction();

            // Konfigürasyon doğrula
            $validated = $this->validateBattleConfig($config);
            if (!$validated['success']) {
                return $validated;
            }

            $battleType = $config['type'] ?? '1v1'; // '1v1', '2v2', '3v3'
            $participants = $config['participants'] ?? [];
            $liveStreamId = $config['live_stream_id'];
            $creatorId = $config['creator_id'];

            // Battle ID oluştur
            $battleId = 'pk_' . $battleType . '_' . uniqid() . '_' . time();

            // Ana PK Battle kaydı oluştur
            $battle = PKBattle::create([
                'live_stream_id' => $liveStreamId,
                'battle_id' => $battleId,
                'challenger_id' => $creatorId,
                'opponent_id' => $participants[0] ?? null,
                'status' => 'PENDING',
                'battle_phase' => 'INVITATION',
                'countdown_duration' => 30,
                'countdown_started_at' => now(),
                'challenger_score' => 0,
                'opponent_score' => 0,
                'challenger_gift_count' => 0,
                'opponent_gift_count' => 0,
                'total_gift_value' => 0,
                'challenger_stream_status' => 'DISCONNECTED',
                'opponent_stream_status' => 'DISCONNECTED',
                'battle_config' => [
                    'type' => $battleType,
                    'max_participants' => $this->getMaxParticipants($battleType),
                    'round_duration' => $config['round_duration'] ?? 60,
                    'total_rounds' => $config['total_rounds'] ?? 3,
                    'participants' => $participants,
                    'teams' => $this->organizeTeams($participants, $battleType),
                    'invite_timeout' => 30,
                    'created_by' => $creatorId,
                    'created_at' => now()->toISOString(),
                    'tiktok_style' => true
                ]
            ]);

            // Katılımcılara davetiye gönder
            foreach ($participants as $participantId) {
                $this->sendPKBattleInvitation($battle, $participantId);
            }

            DB::commit();

            Log::info("Multi-player PK Battle started", [
                'battle_id' => $battleId,
                'type' => $battleType,
                'participants' => $participants
            ]);

            return [
                'success' => true,
                'message' => "PK Battle ({$battleType}) başlatıldı! Katılımcılar davet edildi.",
                'battle' => $battle,
                'battle_id' => $battleId
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PK Battle start failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'PK Battle başlatılamadı: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Start a new PK Battle (legacy method)
     */
    public function startPKBattle(array $config): PKBattle
    {
        try {
            return DB::transaction(function () use ($config) {
                $liveStream = LiveStream::findOrFail($config['live_stream_id']);
                
                // Check if there's already an active PK battle
                $existingBattle = PKBattle::where('live_stream_id', $liveStream->id)
                    ->whereIn('status', ['PENDING', 'ACTIVE'])
                    ->first();
                    
                if ($existingBattle) {
                    throw new \Exception('Bu yayında zaten aktif bir PK savaşı var');
                }
                
                // Create the PK Battle
                $battle = PKBattle::create([
                    'live_stream_id' => $liveStream->id,
                    'challenger_id' => $config['challenger_id'],
                    'opponent_id' => $config['opponent_id'],
                    'status' => 'PENDING',
                    'challenger_score' => 0,
                    'opponent_score' => 0,
                    'started_at' => null,
                    'ended_at' => null,
                    'winner_id' => null,
                ]);
                
                // Update live stream mode
                // $liveStream->update(['mode' => 'pk_battle']); // Disabled since we're not using agora_channels
                
                // Load relationships
                $battle->load(['challenger', 'opponent']);
                
                // Broadcast the update
                broadcast(new PKBattleUpdatedEvent($battle))->toOthers();
                
                Log::info('PK Battle started', [
                    'battle_id' => $battle->id,
                    'challenger_id' => $battle->challenger_id,
                    'opponent_id' => $battle->opponent_id,
                ]);
                
                return $battle;
            });
        } catch (\Exception $e) {
            Log::error('Failed to start PK Battle', [
                'error' => $e->getMessage(),
                'config' => $config,
            ]);
            throw $e;
        }
    }
    
    /**
     * Accept a PK Battle invitation
     */
    public function acceptPKBattle(string $battleId, string $userId): PKBattle
    {
        try {
            return DB::transaction(function () use ($battleId, $userId) {
                $battle = PKBattle::findOrFail($battleId);

                // Validate that the user is the opponent
                if ($battle->opponent_id != $userId) {
                    throw new \Exception('Bu daveti sadece davet edilen kişi kabul edebilir');
                }

                // Check battle status
                if ($battle->status !== 'PENDING') {
                    throw new \Exception('Bu PK savaşı zaten başlamış veya bitmiş');
                }

                // Start the battle
                $battle->start();

                // Load relationships
                $battle->load(['challenger', 'opponent']);

                // Broadcast PKBattleStarted event to both host and opponent streams
                broadcast(new \App\Events\LiveStream\PKBattleStarted($battle))->toOthers();

                // Also start the countdown
                broadcast(new \App\Events\LiveStream\PKBattleCountdownStarted($battle))->toOthers();

                Log::info('PK Battle accepted and started', [
                    'battle_id' => $battle->id,
                    'opponent_id' => $userId,
                    'host_stream_id' => $battle->live_stream_id,
                    'opponent_stream_id' => $battle->opponent_stream_id,
                ]);

                return $battle;
            });
        } catch (\Exception $e) {
            Log::error('Failed to accept PK Battle', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }
    
    /**
     * Update PK Battle score when a gift is received
     */
    public function updateScore(string $battleId, string $receiverId, int $giftValue): PKBattle
    {
        try {
            return DB::transaction(function () use ($battleId, $receiverId, $giftValue) {
                $battle = PKBattle::lockForUpdate()->findOrFail($battleId);
                
                if (!$battle->isActive()) {
                    throw new \Exception('PK savaşı aktif değil');
                }
                
                // Update the appropriate score
                if ($receiverId == $battle->challenger_id) {
                    $battle->challenger_score += $giftValue;
                } elseif ($receiverId == $battle->opponent_id) {
                    $battle->opponent_score += $giftValue;
                } else {
                    throw new \Exception('Geçersiz alıcı ID');
                }
                
                $battle->save();
                
                // Load relationships
                $battle->load(['challenger', 'opponent']);
                
                // Broadcast score update
                broadcast(new PKBattleScoreUpdated($battle))->toOthers();
                
                // Check if someone reached a goal threshold (e.g., every 1000 points)
                $this->checkForGoal($battle, $receiverId, $giftValue);
                
                return $battle;
            });
        } catch (\Exception $e) {
            Log::error('Failed to update PK Battle score', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
                'receiver_id' => $receiverId,
                'gift_value' => $giftValue,
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if a goal was scored (milestone reached)
     */
    protected function checkForGoal(PKBattle $battle, string $scorerId, int $newPoints): void
    {
        $goalThreshold = 1000; // Goal every 1000 points
        
        $oldScore = $scorerId == $battle->challenger_id 
            ? ($battle->challenger_score - $newPoints)
            : ($battle->opponent_score - $newPoints);
            
        $newScore = $scorerId == $battle->challenger_id 
            ? $battle->challenger_score
            : $battle->opponent_score;
        
        $oldGoals = intval($oldScore / $goalThreshold);
        $newGoals = intval($newScore / $goalThreshold);
        
        if ($newGoals > $oldGoals) {
            // Goal scored! Broadcast the event
            broadcast(new PKBattleRoundEnded([
                'battle_id' => $battle->id,
                'goal_scorer_id' => $scorerId,
                'goals_scored' => $newGoals - $oldGoals,
                'total_goals' => $newGoals,
            ]))->toOthers();
            
            Log::info('PK Battle goal scored', [
                'battle_id' => $battle->id,
                'scorer_id' => $scorerId,
                'goals' => $newGoals,
            ]);
        }
    }
    
    /**
     * Handle gift received during PK Battle
     */
    public function handleGift(string $battleId, string $giftId, string $senderId, string $receiverId, int $quantity): void
    {
        try {
            $battle = PKBattle::findOrFail($battleId);
            
            if (!$battle->isActive()) {
                Log::warning('Gift sent to inactive PK Battle', [
                    'battle_id' => $battleId,
                ]);
                return;
            }
            
            // Calculate gift value (you might want to look up the actual gift value from a gifts table)
            $giftValue = $this->calculateGiftValue($giftId, $quantity);
            
            // Update the score
            $this->updateScore($battleId, $receiverId, $giftValue);
            
            // Broadcast gift animation event
            broadcast(new PKBattleGiftReceived([
                'battle_id' => $battleId,
                'gift_id' => $giftId,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'quantity' => $quantity,
                'value' => $giftValue,
                'is_left_side' => $receiverId == $battle->challenger_id,
            ]))->toOthers();
            
        } catch (\Exception $e) {
            Log::error('Failed to handle PK Battle gift', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
                'gift_id' => $giftId,
            ]);
        }
    }
    
    /**
     * Calculate gift value (simplified - you should look this up from your gifts table)
     */
    protected function calculateGiftValue(string $giftId, int $quantity): int
    {
        // This is a simplified calculation
        // In reality, you'd look up the gift value from your database
        $baseValues = [
            'rose' => 10,
            'heart' => 50,
            'diamond' => 100,
            'crown' => 500,
            'rocket' => 1000,
        ];
        
        $baseValue = $baseValues[$giftId] ?? 10;
        return $baseValue * $quantity;
    }
    
    /**
     * End a PK Battle
     */
    public function endPKBattle(string $battleId): PKBattle
    {
        try {
            return DB::transaction(function () use ($battleId) {
                $battle = PKBattle::findOrFail($battleId);
                
                if ($battle->status === 'FINISHED') {
                    throw new \Exception('PK savaşı zaten bitmiş');
                }
                
                // Determine winner
                $winnerId = null;
                if ($battle->challenger_score > $battle->opponent_score) {
                    $winnerId = $battle->challenger_id;
                } elseif ($battle->opponent_score > $battle->challenger_score) {
                    $winnerId = $battle->opponent_id;
                }
                
                // Finish the battle
                $battle->finish($winnerId);
                
                // Update live stream mode back to normal
                // $battle->liveStream->update(['mode' => 'normal']); // Disabled since we're not using agora_channels
                
                // Load relationships
                $battle->load(['challenger', 'opponent', 'winner']);
                
                // Broadcast the end event
                broadcast(new PKBattleEnded($battle))->toOthers();
                
                Log::info('PK Battle ended', [
                    'battle_id' => $battle->id,
                    'winner_id' => $winnerId,
                    'challenger_score' => $battle->challenger_score,
                    'opponent_score' => $battle->opponent_score,
                ]);
                
                return $battle;
            });
        } catch (\Exception $e) {
            Log::error('Failed to end PK Battle', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
            ]);
            throw $e;
        }
    }
    
    /**
     * Get active PK Battle for a live stream
     */
    public function getActiveBattle(string $liveStreamId): ?PKBattle
    {
        return PKBattle::where('live_stream_id', $liveStreamId)
            ->whereIn('status', ['PENDING', 'ACTIVE'])
            ->with(['challenger', 'opponent'])
            ->first();
    }

    // === TikTok Style Battle Helper Methods ===

    /**
     * Battle tipine göre maksimum katılımcı sayısını döndürür
     */
    private function getMaxParticipants(string $battleType): int
    {
        return match($battleType) {
            '1v1' => 2,
            '2v2' => 4,
            '3v3' => 6,
            'royal' => 8,
            default => 2
        };
    }

    /**
     * Katılımcıları takımlara organize eder
     */
    private function organizeTeams(array $participants, string $battleType): array
    {
        $maxParticipants = $this->getMaxParticipants($battleType);
        $teamSize = $maxParticipants / 2;

        $teams = [
            'team_a' => array_slice($participants, 0, $teamSize),
            'team_b' => array_slice($participants, $teamSize, $teamSize)
        ];

        return $teams;
    }

    /**
     * Battle konfigürasyonunu doğrular
     */
    private function validateBattleConfig(array $config): array
    {
        if (empty($config['participants'])) {
            return ['success' => false, 'message' => 'Katılımcı listesi boş olamaz'];
        }

        $battleType = $config['type'] ?? '1v1';
        $maxParticipants = $this->getMaxParticipants($battleType);

        if (count($config['participants']) > $maxParticipants - 1) {
            return ['success' => false, 'message' => "Bu battle tipi için maksimum " . ($maxParticipants - 1) . " katılımcı seçebilirsiniz"];
        }

        if (!empty($config['live_stream_id'])) {
            $stream = AgoraChannel::find($config['live_stream_id']);
            if (!$stream || !$stream->is_online) {
                return ['success' => false, 'message' => 'Canlı yayın bulunamadı veya aktif değil'];
            }
        }

        return ['success' => true];
    }

    /**
     * Katılımcıya PK Battle davetiyesi gönderir
     */
    private function sendPKBattleInvitation(PKBattle $battle, string $participantId): void
    {
        try {
            $participant = User::find($participantId);
            if (!$participant) {
                Log::warning("PK Battle invitation failed: User not found", ['user_id' => $participantId]);
                return;
            }

            $participant->notify(new \App\Notifications\LiveStream\PKBattleInviteNotification($battle));

            Log::info("PK Battle invitation sent", [
                'battle_id' => $battle->battle_id,
                'participant_id' => $participantId,
                'participant_nickname' => $participant->nickname
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send PK Battle invitation", [
                'battle_id' => $battle->battle_id,
                'participant_id' => $participantId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Otomatik katılımcı eşleştirme - TikTok tarzı
     */
    public function findRandomOpponents(string $liveStreamId, string $userId, string $battleType = '1v1'): array
    {
        try {
            $maxOpponents = $this->getMaxParticipants($battleType) - 1;

            $activeViewers = \App\Models\Agora\AgoraChannelViewer::where('agora_channel_id', $liveStreamId)
                ->where('status_id', \App\Models\Agora\AgoraChannelViewer::STATUS_ACTIVE)
                ->where('user_id', '!=', $userId)
                ->where('joined_at', '>=', now()->subMinutes(5))
                ->inRandomOrder()
                ->limit($maxOpponents)
                ->get();

            $opponents = [];
            foreach ($activeViewers as $viewer) {
                if ($viewer->user_data) {
                    $opponents[] = [
                        'id' => $viewer->user_id,
                        'user_data' => $viewer->user_data,
                        'type' => 'active_viewer'
                    ];
                }
            }

            if (count($opponents) < $maxOpponents) {
                $needed = $maxOpponents - count($opponents);
                $existingIds = array_column($opponents, 'id');
                $existingIds[] = $userId;

                $followers = User::whereHas('followers', function ($query) use ($userId) {
                        $query->where('follower_user_id', $userId);
                    })
                    ->whereNotIn('id', $existingIds)
                    ->where('last_seen_at', '>=', now()->subMinutes(30))
                    ->inRandomOrder()
                    ->limit($needed)
                    ->get();

                foreach ($followers as $follower) {
                    $opponents[] = [
                        'id' => $follower->id,
                        'user_data' => [
                            'id' => $follower->id,
                            'name' => $follower->name,
                            'nickname' => $follower->nickname,
                            'avatar' => $follower->avatar
                        ],
                        'type' => 'follower'
                    ];
                }
            }

            return [
                'success' => true,
                'opponents' => array_slice($opponents, 0, $maxOpponents),
                'battle_type' => $battleType
            ];

        } catch (\Exception $e) {
            Log::error('Failed to find random opponents', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Rastgele rakip bulunamadı',
                'opponents' => []
            ];
        }
    }

    /**
     * PK Battle davetini kabul eder
     */
    public function acceptPKBattleInvitation(string $battleId, string $userId): array
    {
        try {
            $battle = PKBattle::where('battle_id', $battleId)->first();
            if (!$battle) {
                return ['success' => false, 'message' => 'PK Battle bulunamadı'];
            }

            if ($battle->status !== 'PENDING') {
                return ['success' => false, 'message' => 'Bu PK Battle artık aktif değil'];
            }

            $config = $battle->battle_config;
            $config['accepted_participants'] = $config['accepted_participants'] ?? [];
            
            if (!in_array($userId, $config['accepted_participants'])) {
                $config['accepted_participants'][] = $userId;
            }

            $battle->battle_config = $config;
            $battle->save();

            $totalExpected = count($config['participants']);
            $totalAccepted = count($config['accepted_participants']);

            if ($totalAccepted >= $totalExpected) {
                $this->startActivePKBattle($battle);
            }

            return [
                'success' => true,
                'message' => 'PK Battle daveti kabul edildi',
                'battle' => $battle,
                'waiting_for' => $totalExpected - $totalAccepted
            ];

        } catch (\Exception $e) {
            Log::error('PK Battle accept failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Davet kabul edilemedi'];
        }
    }

    /**
     * PK Battle'ı aktif aşamaya geçirir
     */
    private function startActivePKBattle(PKBattle $battle): void
    {
        $battle->status = 'ACTIVE';
        $battle->battle_phase = 'COUNTDOWN';
        $battle->countdown_started_at = now();
        $battle->countdown_duration = 10;
        $battle->started_at = now();
        $battle->save();

        broadcast(new \App\Events\LiveStream\PKBattleCountdownStarted($battle))->toOthers();

        Log::info('PK Battle started (active phase)', ['battle_id' => $battle->battle_id]);
    }
}