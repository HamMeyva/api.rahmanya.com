<?php

namespace App\GraphQL\Resolvers\Agora;

use App\Models\PKBattle;
use App\Models\PKBattleStateLog;
use App\Models\Agora\AgoraChannel;
use App\Events\LiveStream\PKBattleCountdownStarted;
use App\Events\LiveStream\PKBattleTimerSync;
use App\Events\LiveStream\PKBattleStreamStatusUpdated;
use App\Events\PKBattleInvitationEvent;
use App\Notifications\LiveStream\PKBattleInviteNotification;
use App\Services\LiveStream\EnhancedPKBattleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraPKBattleResolver
{
    public $withinTransaction = false; // Disable automatic transaction wrapping

    protected EnhancedPKBattleService $pkBattleService;

    public function __construct(EnhancedPKBattleService $pkBattleService)
    {
        $this->pkBattleService = $pkBattleService;
    }

    public function inviteToPKBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $input = $args['input'];

        try {
            $liveStream = AgoraChannel::findOrFail($input['liveStreamId']);

            if ($liveStream->user_id !== $user->id) {
                throw new \Exception('Bu yayını sadece sahibi PK battle başlatabilir');
            }

            // 🎯 FIX: Check if there is an ACTUAL active battle, not just the flag
            // If isPKMode is true but no active battle exists (zombie state), allow new battle
            $activeBattle = PKBattle::select('id', 'live_stream_id', 'status')
                ->where('live_stream_id', $input['liveStreamId'])
                ->where('status', 'ACTIVE')
                ->first();

            if ($liveStream->isPKMode() && $activeBattle) {
                throw new \Exception('Bu yayında zaten aktif bir PK battle var');
            }

            // If in PK mode but no active battle, log warning and proceed (auto-fix)
            if ($liveStream->isPKMode() && !$activeBattle) {
                \Log::warning('PK Battle: Stream is in PK mode but no active battle found. Auto-fixing.', [
                    'live_stream_id' => $input['liveStreamId']
                ]);
            }

            // 🎯 FIX: Get opponent stream ID (frontend will provide it, or find opponent's stream)
            $opponentStreamId = $input['opponentStreamId'] ?? null;

            // If not provided, try to find opponent's active stream
            if (!$opponentStreamId) {
                $opponentUser = \App\Models\User::find($input['opponentId']);
                if ($opponentUser) {
                    // Find opponent's active main stream or cohost stream
                    $opponentStream = AgoraChannel::where('user_id', $opponentUser->id)
                        ->where('status_id', AgoraChannel::STATUS_LIVE)
                        ->where('is_online', true)
                        ->latest()
                        ->first();

                    if ($opponentStream) {
                        $opponentStreamId = (string) $opponentStream->_id;
                    }
                }
            }

            // 🎯 FIX: Get all cohost stream IDs from the host's live stream
            $cohostStreamIds = [];

            // Use cohost_channel_ids field from the host channel
            if (!empty($liveStream->cohost_channel_ids)) {
                $cohostChannels = AgoraChannel::whereIn('_id', $liveStream->cohost_channel_ids)
                    ->where('status_id', AgoraChannel::STATUS_LIVE)
                    ->where('is_online', true)
                    ->get()
                    ->map(function ($channel) {
                        return (string) $channel->_id;
                    })
                    ->toArray();

                if (!empty($cohostChannels)) {
                    $cohostStreamIds = $cohostChannels;
                }
            }

            // 🚨 VALIDATION REMOVED: Trust frontend's opponentStreamId
            // Cohost streams are virtual/ephemeral and may not be in AgoraChannel table
            // Frontend knows the correct cohost stream ID from ZegoLiveController
            // Validation was causing opponent_stream_id to be set to null, preventing WebSocket events
            if ($opponentStreamId) {
                \Log::info("PK Battle: Using opponent stream ID from frontend", [
                    'opponent_stream_id' => $opponentStreamId,
                    'opponent_id' => $input['opponentId']
                ]);
            }

            // ✅ FIX: Normalize opponent user ID to UUID with dashes
            // Frontend may send: "9fe4c72e19004691b37e3e7b3fcbe3d7" (no dashes)
            // Or: "cohost_9fe4c72e19004691b37e3e7b3fcbe3d7_1763677602915" (cohost stream ID)
            // Database needs: "9fe4c72e-1900-4691-b37e-3e7b3fcbe3d7" (with dashes)
            $opponentUserId = $input['opponentId'];

            // Case 1: Cohost stream ID format
            if (str_starts_with($opponentUserId, 'cohost_')) {
                preg_match('/^cohost_([a-f0-9]{32})_\d+$/', $opponentUserId, $matches);
                if (isset($matches[1])) {
                    $uuidWithoutDashes = $matches[1];
                    $opponentUserId = substr($uuidWithoutDashes, 0, 8) . '-' .
                        substr($uuidWithoutDashes, 8, 4) . '-' .
                        substr($uuidWithoutDashes, 12, 4) . '-' .
                        substr($uuidWithoutDashes, 16, 4) . '-' .
                        substr($uuidWithoutDashes, 20, 12);

                    \Log::info("PK Battle: Extracted user ID from cohost stream ID", [
                        'original' => $input['opponentId'],
                        'extracted_user_id' => $opponentUserId,
                    ]);
                }
            }
            // Case 2: UUID without dashes (32 hex chars)
            elseif (preg_match('/^[a-f0-9]{32}$/i', $opponentUserId)) {
                $uuidWithoutDashes = $opponentUserId;
                $opponentUserId = substr($uuidWithoutDashes, 0, 8) . '-' .
                    substr($uuidWithoutDashes, 8, 4) . '-' .
                    substr($uuidWithoutDashes, 12, 4) . '-' .
                    substr($uuidWithoutDashes, 16, 4) . '-' .
                    substr($uuidWithoutDashes, 20, 12);

                \Log::info("PK Battle: Normalized UUID without dashes to standard format", [
                    'original' => $input['opponentId'],
                    'normalized_user_id' => $opponentUserId,
                ]);
            }
            // Case 3: Already properly formatted UUID with dashes - no change needed
            else {
                \Log::info("PK Battle: Using opponent ID as-is (already has dashes)", [
                    'opponent_id' => $opponentUserId,
                ]);
            }

            $battle = PKBattle::create([
                'live_stream_id' => $input['liveStreamId'],    // Use UUID directly
                'challenger_id' => $user->id,                  // Use UUID directly
                'opponent_id' => $opponentUserId,              // ✅ FIXED: Use extracted user ID
                'opponent_stream_id' => $opponentStreamId,     // ✅ ADDED
                'cohost_stream_ids' => $cohostStreamIds,       // ✅ ADDED
                'status' => 'PENDING',
                'battle_phase' => 'COUNTDOWN',
                'countdown_duration' => 10,
                'total_rounds' => $input['totalRounds'] ?? 3,           // ✅ Devre sayısı (default: 3)
                'current_round' => 1,                                    // ✅ Başlangıç devresi
                'round_duration_minutes' => $input['roundDurationMinutes'] ?? 5, // ✅ Devre süresi (default: 5)
                'challenger_stream_status' => 'DISCONNECTED',
                'opponent_stream_status' => 'DISCONNECTED',
                'battle_config' => [
                    'created_by' => $user->id,
                    'created_at' => now()->toISOString(),
                    'opponent_stream_found' => !is_null($opponentStreamId),
                    'cohost_count' => count($cohostStreamIds),
                ]
            ]);

            // Log battle creation
            PKBattleStateLog::logEvent(
                $battle->id,
                'battle_created',
                [
                    'challenger_id' => $user->id,
                    'opponent_id' => $input['opponentId'],
                    'live_stream_id' => $input['liveStreamId'],
                ]
            );

            // Yayın modunu güncelle
            $liveStream->update(['mode' => 'pk_battle']);

            // 🔔 Broadcast invitation event to WebSocket channels
            broadcast(new PKBattleInvitationEvent($battle))->toOthers();

            // 🔔 Send push notification to opponent
            try {
                $opponent = \App\Models\User::find($input['opponentId']);
                if ($opponent) {
                    $opponent->notify(new PKBattleInviteNotification($battle));
                    \Log::info('PK Battle notification sent', [
                        'battle_id' => $battle->id,
                        'opponent_id' => $input['opponentId']
                    ]);
                }
            } catch (\Exception $notifError) {
                \Log::warning('PK Battle notification failed', [
                    'error' => $notifError->getMessage(),
                    'battle_id' => $battle->id
                ]);
            }

            return [
                'success' => true,
                'message' => 'PK battle daveti gönderildi',
                'battle' => $battle,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
            ];
        }
    }

    public function acceptPKBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $battleId = $args['battleId'];

        try {
            $battle = PKBattle::findOrFail($battleId);

            // Normalize UUIDs for comparison (remove dashes)
            $normalizedOpponentId = str_replace('-', '', $battle->opponent_id);
            $normalizedUserId = str_replace('-', '', $user->id);

            \Log::info('PK Accept: UUID comparison', [
                'battle_opponent_id' => $battle->opponent_id,
                'user_id' => $user->id,
                'normalized_opponent' => $normalizedOpponentId,
                'normalized_user' => $normalizedUserId,
            ]);

            // Check using normalized UUID comparison
            if ($normalizedOpponentId !== $normalizedUserId) {
                throw new \Exception('Bu daveti sadece davet edilen kişi kabul edebilir');
            }

            if ($battle->status !== 'PENDING') {
                throw new \Exception('Bu davet artık geçerli değil');
            }

            // ✅ Update opponent stream ID if not set (opponent accepting battle)
            $updateData = [
                'status' => 'ACTIVE',
                'battle_phase' => 'COUNTDOWN',
                'countdown_started_at' => now(),
                'server_sync_time' => now(),
                'last_activity_at' => now(),
                'started_at' => now(),
            ];

            // Capture opponent's stream ID if not already set
            if (!$battle->opponent_stream_id) {
                $opponentStream = AgoraChannel::where('user_id', $user->id)
                    ->where('status_id', AgoraChannel::STATUS_LIVE)
                    ->where('is_online', true)
                    ->first();

                if ($opponentStream) {
                    $updateData['opponent_stream_id'] = (string) $opponentStream->_id;
                }
            }

            $battle->update($updateData);

            // Reload battle with relations for events
            $battle = $battle->fresh(['challenger', 'opponent']);

            // 🚨 FIX: Manually load opponent if relation failed (UUID format issue)
            if (!$battle->opponent && $battle->opponent_id) {
                \Log::warning('PK Accept: Opponent relation failed to load, trying manual lookup', [
                    'opponent_id' => $battle->opponent_id,
                ]);

                // Try to find user by normalizing UUID (with and without dashes)
                $opponentId = $battle->opponent_id;
                $opponent = \App\Models\User::find($opponentId);

                // If not found, try with dashes added
                if (!$opponent && !str_contains($opponentId, '-')) {
                    $opponentIdWithDashes = substr($opponentId, 0, 8) . '-' .
                        substr($opponentId, 8, 4) . '-' .
                        substr($opponentId, 12, 4) . '-' .
                        substr($opponentId, 16, 4) . '-' .
                        substr($opponentId, 20);
                    $opponent = \App\Models\User::find($opponentIdWithDashes);

                    if ($opponent) {
                        \Log::info('PK Accept: Found opponent with dashed UUID', [
                            'original_id' => $opponentId,
                            'dashed_id' => $opponentIdWithDashes,
                        ]);
                    }
                }

                // Manually set the relation
                if ($opponent) {
                    $battle->setRelation('opponent', $opponent);
                }
            }

            \Log::info('PK Accept: Battle updated and relations loaded', [
                'battle_id' => $battle->id,
                'has_challenger' => !is_null($battle->challenger),
                'has_opponent' => !is_null($battle->opponent),
                'opponent_name' => $battle->opponent ? $battle->opponent->name : 'N/A',
                'status' => $battle->status,
            ]);

            // ✅ NEW: Create Challenge record for admin panel when PK battle is accepted
            try {
                $challenge = \App\Models\Challenge\Challenge::create([
                    'agora_channel_id' => $battle->live_stream_id,
                    'pk_battle_id' => $battle->id,  // Link to PK Battle
                    'type_id' => \App\Models\Challenge\Challenge::TYPE_1v1,  // PK battles are always 1v1
                    'status_id' => \App\Models\Challenge\Challenge::STATUS_ACTIVE,
                    'started_at' => now(),
                    'round_count' => $battle->total_rounds ?? 1,
                    'current_round' => 1,
                    'round_duration' => ($battle->round_duration_minutes ?? 5) * 60,  // Convert minutes to seconds
                    'max_coins' => $battle->shoots_per_goal ?? 1000,  // SHOOT points per goal
                ]);

                // Create challenge teams for both participants
                \App\Models\Challenge\ChallengeTeam::create([
                    'challenge_id' => $challenge->_id,
                    'team_no' => 1,
                    'user_id' => $battle->challenger_id,
                    'score' => 0,
                    'is_winner' => false,
                ]);

                \App\Models\Challenge\ChallengeTeam::create([
                    'challenge_id' => $challenge->_id,
                    'team_no' => 2,
                    'user_id' => $battle->opponent_id,
                    'score' => 0,
                    'is_winner' => false,
                ]);

                // Create first round record
                \App\Models\Challenge\ChallengeRound::create([
                    'challenge_id' => $challenge->_id,
                    'round_number' => 1,
                    'started_at' => now(),
                ]);

                // Update PK Battle with challenge_id
                $battle->update(['challenge_id' => (string) $challenge->_id]);

                \Log::info('PK Accept: Challenge created and linked to PK Battle', [
                    'battle_id' => $battle->id,
                    'challenge_id' => $challenge->_id,
                    'challenger_id' => $battle->challenger_id,
                    'opponent_id' => $battle->opponent_id,
                ]);
            } catch (\Exception $e) {
                \Log::error('PK Accept: Failed to create Challenge record', [
                    'battle_id' => $battle->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Don't fail the whole operation if Challenge creation fails
            }

            // Log battle acceptance and start countdown
            PKBattleStateLog::logEvent(
                $battle->id,
                'countdown_started',
                [
                    'accepted_by' => $user->id,
                    'countdown_duration' => $battle->countdown_duration,
                ],
                $user->id
            );

            // 🚨 CRITICAL: Broadcast battle started event to show PK UI on all devices
            // REMOVED ->toOthers() so ALL participants (including acceptor) receive the event
            \Log::info('PK Accept: Broadcasting PKBattleStarted event', [
                'battle_id' => $battle->id,
                'host_stream_id' => $battle->live_stream_id,
                'opponent_stream_id' => $battle->opponent_stream_id,
                'opponent_stream_id_is_null' => is_null($battle->opponent_stream_id),
                'opponent_stream_id_type' => gettype($battle->opponent_stream_id),
            ]);

            \Log::info('PK Accept: PKBattleStarted event will broadcast to channels:', [
                'channel_1' => 'live-stream.' . $battle->live_stream_id,
                'channel_2' => $battle->opponent_stream_id ? 'live-stream.' . $battle->opponent_stream_id : 'NONE - opponent_stream_id is null!',
            ]);

            broadcast(new \App\Events\LiveStream\PKBattleStarted($battle));

            // Also broadcast countdown started for timer sync
            broadcast(new PKBattleCountdownStarted($battle));

            return [
                'success' => true,
                'message' => 'PK battle kabul edildi',
                'battle' => $battle->fresh(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
            ];
        }
    }

    public function rejectPKBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $battleId = $args['battleId'];

        try {
            $battle = PKBattle::findOrFail($battleId);

            // Check using direct UUID comparison
            if ($battle->opponent_id !== $user->id) {
                throw new \Exception('Bu daveti sadece davet edilen kişi reddedebilir');
            }

            $battle->update(['status' => 'CANCELLED']);

            // Update stream mode to normal using live_stream_id directly
            $liveStream = AgoraChannel::find($battle->live_stream_id);
            if ($liveStream) {
                $liveStream->update(['mode' => 'normal']);
            }

            return [
                'success' => true,
                'message' => 'PK battle reddedildi',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function endPKBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $battleId = $args['battleId'];

        try {
            $battle = PKBattle::findOrFail($battleId);

            // Check using direct UUID comparison
            if (!in_array($user->id, [$battle->challenger_id, $battle->opponent_id])) {
                throw new \Exception('Bu battle\'ı sadece katılımcılar bitirebilir');
            }

            // Determine winner based on scores (winner_id will store UUID directly)
            $winnerId = null;
            if ($battle->challenger_score > $battle->opponent_score) {
                $winnerId = $battle->challenger_id;
            } elseif ($battle->opponent_score > $battle->challenger_score) {
                $winnerId = $battle->opponent_id;
            }

            $battle->update([
                'status' => 'FINISHED',
                'ended_at' => now(),
                'winner_id' => $winnerId,
            ]);

            // ✅ NEW: Update linked Challenge record when PK battle ends
            if ($battle->challenge_id) {
                try {
                    $challenge = \App\Models\Challenge\Challenge::find($battle->challenge_id);

                    if ($challenge) {
                        // Update challenge status
                        $challenge->update([
                            'status_id' => \App\Models\Challenge\Challenge::STATUS_FINISHED,
                            'ended_at' => now(),
                        ]);

                        // Update team scores and winner
                        $challengerTeam = \App\Models\Challenge\ChallengeTeam::where('challenge_id', $challenge->_id)
                            ->where('user_id', $battle->challenger_id)
                            ->first();

                        $opponentTeam = \App\Models\Challenge\ChallengeTeam::where('challenge_id', $challenge->_id)
                            ->where('user_id', $battle->opponent_id)
                            ->first();

                        if ($challengerTeam) {
                            $challengerTeam->update([
                                'score' => $battle->challenger_score ?? 0,
                                'is_winner' => $winnerId === $battle->challenger_id,
                            ]);
                        }

                        if ($opponentTeam) {
                            $opponentTeam->update([
                                'score' => $battle->opponent_score ?? 0,
                                'is_winner' => $winnerId === $battle->opponent_id,
                            ]);
                        }

                        \Log::info('PK End: Challenge updated', [
                            'battle_id' => $battle->id,
                            'challenge_id' => $challenge->_id,
                            'winner_id' => $winnerId,
                            'challenger_score' => $battle->challenger_score,
                            'opponent_score' => $battle->opponent_score,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('PK End: Failed to update Challenge', [
                        'battle_id' => $battle->id,
                        'challenge_id' => $battle->challenge_id,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the whole operation if Challenge update fails
                }
            }

            // Update stream mode to normal using live_stream_id directly
            $liveStream = AgoraChannel::find($battle->live_stream_id);
            if ($liveStream) {
                $liveStream->update(['mode' => 'normal']);
            }

            return [
                'success' => true,
                'message' => 'PK battle tamamlandı',
                'battle' => $battle->fresh(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
            ];
        }
    }

    public function activePKBattles($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return PKBattle::with(['liveStream', 'challenger', 'opponent', 'winner'])
            ->where('status', 'ACTIVE')
            ->get();
    }

    public function pendingPKBattleInvitations($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();

        return PKBattle::with(['liveStream', 'challenger', 'opponent', 'winner'])
            ->where('status', 'PENDING')
            ->where('opponent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function testNotifications($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();

        return $user->notifications()
            ->whereDate('created_at', '>=', now()->subDays(3))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });
    }

    public function pkBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return PKBattle::with(['liveStream', 'challenger', 'opponent', 'winner'])
            ->findOrFail($args['id']);
    }

    /**
     * Get active PK battle for a specific stream
     * Used by late joiners and cohosts to sync PK UI state
     *
     * @param mixed $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return PKBattle|null
     */
    public function activePKBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?PKBattle
    {
        $streamId = $args['streamId'];

        Log::info('🎮 PK Query: Looking for active battle', [
            'stream_id' => $streamId,
            'user_id' => $context->user()->id ?? 'unknown',
        ]);

        // Find active battle where this stream is involved as:
        // 1. Host stream (live_stream_id - using UUID directly)
        // 2. Opponent stream (opponent_stream_id - using UUID directly)
        // 3. Cohost stream (in cohost_stream_ids array)

        $battle = PKBattle::with(['challenger', 'opponent', 'winner'])
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($streamId) {
                // Check if host stream (using UUID directly)
                $query->where('live_stream_id', $streamId)
                    // OR opponent stream (using UUID directly)
                    ->orWhere('opponent_stream_id', $streamId)
                    // OR in cohost streams array
                    ->orWhereJsonContains('cohost_stream_ids', $streamId);
            })
            ->first();

        if ($battle) {
            Log::info('🎮 PK Query: ✅ Found active battle!', [
                'battle_id' => $battle->id,
                'live_stream_id' => $battle->live_stream_id,
                'opponent_stream_id' => $battle->opponent_stream_id,
                'cohost_stream_ids' => $battle->cohost_stream_ids,
                'status' => $battle->status,
            ]);
        } else {
            Log::info('🎮 PK Query: ❌ No active battle found for stream', [
                'stream_id' => $streamId,
            ]);

            // Debug: List all active battles
            $allActive = PKBattle::where('status', 'ACTIVE')->get(['id', 'live_stream_id', 'opponent_stream_id', 'cohost_stream_ids']);
            Log::info('🎮 PK Query: All active battles', [
                'count' => $allActive->count(),
                'battles' => $allActive->toArray(),
            ]);
        }

        return $battle;
    }

    public function syncBattleTimer($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $battleId = $args['battle_id'];

        try {
            $battle = PKBattle::findOrFail($battleId);

            // Update server sync time
            $battle->update([
                'server_sync_time' => now(),
                'last_activity_at' => now(),
            ]);

            $countdownRemaining = 0;
            if ($battle->countdown_started_at && $battle->battle_phase === 'COUNTDOWN') {
                $elapsed = now()->diffInSeconds($battle->countdown_started_at);
                $countdownRemaining = max(0, $battle->countdown_duration - $elapsed);

                // Auto-transition to active if countdown finished
                if ($countdownRemaining <= 0 && $battle->battle_phase === 'COUNTDOWN') {
                    $battle->update(['battle_phase' => 'ACTIVE']);

                    PKBattleStateLog::logEvent(
                        $battle->id,
                        'battle_started',
                        ['auto_started' => true],
                        $user->id
                    );
                }
            }

            // Broadcast timer sync
            broadcast(new PKBattleTimerSync($battle, [
                'synced_by' => $user->id,
                'countdown_remaining' => $countdownRemaining,
            ]));

            return [
                'success' => true,
                'battle_id' => $battle->id,
                'server_time' => now(),
                'countdown_remaining' => $countdownRemaining,
                'battle_phase' => $battle->battle_phase,
                'is_synced' => true,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'battle_id' => $battleId,
                'server_time' => now(),
                'countdown_remaining' => 0,
                'battle_phase' => 'ENDED',
                'is_synced' => false,
            ];
        }
    }

    public function syncBattleState($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $input = $args['input'];

        try {
            $battle = PKBattle::findOrFail($input['battle_id']);

            $updateData = [
                'server_sync_time' => now(),
                'last_activity_at' => now(),
            ];

            // Update battle phase if provided
            if (isset($input['battle_phase'])) {
                $updateData['battle_phase'] = strtoupper($input['battle_phase']);
            }

            $battle->update($updateData);

            // Log sync event
            PKBattleStateLog::logEvent(
                $battle->id,
                'timer_synced',
                [
                    'client_timestamp' => $input['client_timestamp'],
                    'battle_phase' => $input['battle_phase'] ?? null,
                ],
                $user->id,
                $input['client_timestamp'] ? new \DateTime($input['client_timestamp']) : null
            );

            // Broadcast sync
            broadcast(new PKBattleTimerSync($battle, [
                'synced_by' => $user->id,
                'client_timestamp' => $input['client_timestamp'],
            ]));

            return [
                'success' => true,
                'message' => 'Battle state synchronized',
                'battle' => $battle->fresh(),
                'server_time' => now(),
                'sync_data' => [
                    'synced_by' => $user->id,
                    'sync_timestamp' => now(),
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
                'server_time' => now(),
                'sync_data' => null,
            ];
        }
    }

    public function updateStreamStatus($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $input = $args['input'];

        try {
            $battle = PKBattle::findOrFail($input['battle_id']);

            // Determine which stream status to update using direct UUID comparison
            $updateData = ['last_activity_at' => now()];

            if ($user->id === $battle->challenger_id) {
                $updateData['challenger_stream_status'] = strtoupper($input['stream_status']);
            } elseif ($user->id === $battle->opponent_id) {
                $updateData['opponent_stream_status'] = strtoupper($input['stream_status']);
            } else {
                throw new \Exception('User is not a participant in this battle');
            }

            // Log error data if provided
            if (isset($input['error_data']) && strtoupper($input['stream_status']) === 'DISCONNECTED') {
                $errorLogs = $battle->error_logs ?? [];
                $errorLogs[] = [
                    'user_id' => $user->id,
                    'error_data' => $input['error_data'],
                    'timestamp' => now()->toISOString(),
                ];
                $updateData['error_logs'] = $errorLogs;
            }

            $battle->update($updateData);

            // Log stream status change
            PKBattleStateLog::logEvent(
                $battle->id,
                strtoupper($input['stream_status']) === 'CONNECTED' ? 'stream_connected' : 'stream_disconnected',
                [
                    'stream_status' => strtoupper($input['stream_status']),
                    'error_data' => $input['error_data'] ?? null,
                ],
                $user->id
            );

            // Broadcast stream status update
            broadcast(new PKBattleStreamStatusUpdated(
                $battle,
                $user->id,
                $input['stream_status'],
                $input['error_data'] ?? null
            ));

            return [
                'success' => true,
                'message' => 'Stream status updated',
                'battle' => $battle->fresh(),
                'server_time' => now(),
                'sync_data' => [
                    'updated_by' => $user->id,
                    'stream_status' => $input['stream_status'],
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
                'server_time' => now(),
                'sync_data' => null,
            ];
        }
    }

    public function logBattleEvent($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $input = $args['input'];

        try {
            $battle = PKBattle::findOrFail($input['battle_id']);

            // Update last activity
            $battle->update(['last_activity_at' => now()]);

            // Log the event
            PKBattleStateLog::logEvent(
                $battle->id,
                $input['event_type'],
                $input['event_data'] ?? null,
                $user->id,
                isset($input['client_timestamp']) ? new \DateTime($input['client_timestamp']) : null
            );

            return [
                'success' => true,
                'message' => 'Event logged successfully',
                'battle' => $battle->fresh(),
                'server_time' => now(),
                'sync_data' => [
                    'event_type' => $input['event_type'],
                    'logged_by' => $user->id,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
                'server_time' => now(),
                'sync_data' => null,
            ];
        }
    }

    // === TikTok Style PK Battle Methods ===

    /**
     * TikTok tarzı çoklu katılımcılı PK Battle başlatır
     */
    public function startMultiPlayerPKBattle($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $input = $args['input'];

        try {
            $config = [
                'live_stream_id' => $input['live_stream_id'],
                'creator_id' => $user->id,
                'type' => $input['type'],
                'participants' => $input['participants'],
                'round_duration' => $input['round_duration'] ?? 60,
                'total_rounds' => $input['total_rounds'] ?? 3,
            ];

            $result = $this->pkBattleService->startMultiPlayerPKBattle($config);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'battle' => $result['battle'] ?? null,
                'battle_id' => $result['battle_id'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'PK Battle başlatılamadı: ' . $e->getMessage(),
                'battle' => null,
                'battle_id' => null,
            ];
        }
    }

    /**
     * Rastgele rakipleri bulur - TikTok tarzı eşleştirme
     */
    public function findRandomOpponents($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();

        try {
            $liveStreamId = $args['live_stream_id'];
            $battleType = $args['battle_type'] ?? '1v1';

            $result = $this->pkBattleService->findRandomOpponents($liveStreamId, $user->id, $battleType);

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
                'opponents' => $result['opponents'] ?? [],
                'battle_type' => $result['battle_type'] ?? $battleType,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Rakip bulunamadı: ' . $e->getMessage(),
                'opponents' => [],
                'battle_type' => $args['battle_type'] ?? '1v1',
            ];
        }
    }

    /**
     * PK Battle davetini kabul eder (enhanced version)
     */
    public function acceptPKBattleInvitation($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $battleId = $args['battleId'];

        try {
            $result = $this->pkBattleService->acceptPKBattleInvitation($battleId, $user->id);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'battle' => $result['battle'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Davet kabul edilemedi: ' . $e->getMessage(),
                'battle' => null,
            ];
        }
    }
}
