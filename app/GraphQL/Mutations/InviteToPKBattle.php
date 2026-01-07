<?php

namespace App\GraphQL\Mutations;

use App\Models\PKBattle;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Events\PKBattleInvitationEvent;
use App\Notifications\LiveStream\PKBattleInviteNotification;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Log;

class InviteToPKBattle
{
    public $withinTransaction = false; // Disable automatic transaction wrapping

    /**
     * Ensure the ID is in proper UUID format
     */
    private function ensureUuidFormat($id): string
    {
        $id = (string) $id;
        
        // If it's already a proper UUID, return it
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return $id;
        }
        
        // If it's a numeric ID, try to find the user by various methods
        if (is_numeric($id)) {
            // Try agora_uid first
            $user = User::where('agora_uid', $id)->first();
            if ($user) {
                return $user->id;
            }
            
            // If numeric ID is too large for agora_uid, it might be a legacy MongoDB ObjectId converted to int
            // In this case, throw an error asking for proper UUID
            throw new \Exception("Invalid user ID format. Please use proper UUID instead of numeric ID: {$id}");
        }
        
        // Return as-is and let the validation handle it
        return $id;
    }

    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        $input = $args['input'];
        
        try {
            Log::info('PK Battle invitation request', [
                'challenger_id' => $user->id,
                'challenger_id_type' => gettype($user->id),
                'opponent_id' => $input['opponentId'] ?? null,
                'opponent_id_type' => gettype($input['opponentId'] ?? null),
                'live_stream_id' => $input['liveStreamId'] ?? null
            ]);

            // For Zego, we'll use the room ID directly as live_stream_id
            $zegoRoomId = $input['liveStreamId'];
            $liveStreamId = $zegoRoomId; // Use Zego room ID directly
            
            Log::info('Using Zego room ID as live_stream_id', [
                'zego_room_id' => $zegoRoomId,
                'live_stream_id' => $liveStreamId
            ]);
            
            // Use UUIDs directly for user references - ensure proper format
            $challengerUuid = $this->ensureUuidFormat($user->id);
            $opponentUuid = $this->ensureUuidFormat($input['opponentId']);
            
            // Check for existing active PK battle
            $existingBattle = PKBattle::where('live_stream_id', $liveStreamId)
                ->whereIn('status', ['PENDING', 'ACTIVE'])
                ->first();
                
            if ($existingBattle) {
                throw new \Exception('Aktif PK savaşı zaten var');
            }
            
            // Verify opponent exists
            $opponent = User::findOrFail($input['opponentId']);

            // Get opponent stream ID from input (cohost stream ID)
            $opponentStreamId = $input['opponentStreamId'] ?? null;

            Log::info('PK Battle stream IDs', [
                'host_stream_id' => $liveStreamId,
                'opponent_stream_id' => $opponentStreamId,
            ]);

            // Create PK Battle with proper IDs
            $battle = PKBattle::create([
                'live_stream_id' => $liveStreamId, // string Zego room ID
                'opponent_stream_id' => $opponentStreamId, // Cohost stream ID for broadcasting
                'battle_id' => 'pk_' . uniqid() . '_' . time(), // Unique battle ID
                'challenger_id' => $challengerUuid, // UUID from users
                'opponent_id' => $opponentUuid, // UUID from users
                'status' => 'PENDING', // GraphQL enum value
                'battle_phase' => 'COUNTDOWN', // GraphQL enum value
                'countdown_duration' => 10,
                'challenger_score' => 0,
                'opponent_score' => 0,
                'challenger_gift_count' => 0,
                'opponent_gift_count' => 0,
                'total_gift_value' => 0,
                'challenger_stream_status' => 'DISCONNECTED', // GraphQL enum value
                'opponent_stream_status' => 'DISCONNECTED', // GraphQL enum value
                'battle_config' => [
                    'created_by' => $user->id,
                    'created_at' => now()->toISOString(),
                    'zego_room_id' => $zegoRoomId,
                    'total_rounds' => $input['totalRounds'] ?? 1,
                    'round_duration' => $input['roundDurationMinutes'] ?? 5,
                ],
                'started_at' => null,
                'ended_at' => null,
                'original_live_stream_id' => $zegoRoomId, // Store Zego room ID
                'original_challenger_id' => $user->id,
                'original_opponent_id' => $opponent->id
            ]);
            
            // No need to update live stream mode since we're not using a database record
            
            // Attach user objects with proper relations
            $battle->challenger = $user;
            $battle->opponent = $opponent;
            
            // Broadcast the invitation event via WebSocket
            broadcast(new PKBattleInvitationEvent($battle))->toOthers();
            
            // Send push notification to opponent
            try {
                $opponent->notify(new PKBattleInviteNotification($battle));
            } catch (\Exception $notifError) {
                Log::warning('PK Battle notification failed', [
                    'error' => $notifError->getMessage(),
                    'battle_id' => $battle->id
                ]);
            }
            
            Log::info('PK Battle invitation sent successfully', [
                'battle_id' => $battle->id,
                'challenger' => $user->nickname ?? $user->name,
                'opponent' => $opponent->nickname ?? $opponent->name
            ]);
            
            return [
                'success' => true,
                'message' => 'PK savaşı daveti gönderildi',
                'battle' => $battle
            ];
            
        } catch (\Exception $e) {
            Log::error('PK Battle invitation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null
            ];
        }
    }
}
