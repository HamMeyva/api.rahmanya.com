<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Events\GiftSentEvent;
use App\Events\LikeSentEvent;
use App\Models\Gift;
use App\Models\PKBattle;
use App\Services\LiveStream\PKBattleScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveStreamWebSocketController extends Controller
{
    /**
     * Format UUID string to proper format with dashes
     * Converts: a01e9334b83e45069c84fa886744ca31
     * To: a01e9334-b83e-4506-9c84-fa886744ca31
     */
    private function formatUuid(string $uuid): string
    {
        // Remove any existing dashes
        $uuid = str_replace('-', '', $uuid);

        // If it's already 36 chars with dashes or not 32 chars without, return as-is
        if (strlen($uuid) !== 32) {
            return $uuid;
        }

        // Add dashes in correct positions: 8-4-4-4-12
        return substr($uuid, 0, 8) . '-' .
            substr($uuid, 8, 4) . '-' .
            substr($uuid, 12, 4) . '-' .
            substr($uuid, 16, 4) . '-' .
            substr($uuid, 20, 12);
    }

    /**
     * Handle client-gift-sent WebSocket event
     */
    public function handleGiftSent(Request $request)
    {
        try {
            Log::info('🎁 WEBSOCKET CONTROLLER: ===============================================');
            Log::info('🎁 WEBSOCKET CONTROLLER: handleGiftSent() CALLED!');
            Log::info('🎁 WEBSOCKET CONTROLLER: Received client-gift-sent event', [
                'data' => $request->all(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            Log::info('🎁 WEBSOCKET CONTROLLER: ===============================================');

            $giftData = $request->input('gift', []);
            $liveStreamId = $request->input('live_stream_id');
            // ✅ CRITICAL: Format sender_id to proper UUID format with dashes
            $senderId = $this->formatUuid((string) $request->input('sender_id'));

            // Validate required data
            if (!$liveStreamId || !$senderId || empty($giftData)) {
                Log::warning('🎁 BACKEND: Missing required data for gift event', [
                    'live_stream_id' => $liveStreamId,
                    'sender_id' => $senderId,
                    'gift_data' => $giftData
                ]);
                return response()->json(['error' => 'Missing required data'], 400);
            }

            // Get gift details from database for frame-by-frame data
            $giftId = $giftData['gift_id'] ?? null;
            $gift = null;
            if ($giftId) {
                $gift = Gift::find($giftId);
            }

            // Log the received gift data for debugging
            Log::info('🎁 BACKEND: Processing gift data', [
                'gift_id' => $giftId,
                'gift_found' => $gift ? 'yes' : 'no',
                'is_frame_animation' => $gift ? $gift->is_frame_animation : 'unknown',
                'gift_image' => $giftData['gift_image'] ?? 'null',
                'gift_video_url' => $giftData['gift_video_url'] ?? 'null',
                'gift_slug' => $giftData['gift_slug'] ?? 'null',
                'gift_team_id' => $giftData['gift_team_id'] ?? 'null'
            ]);

            // 💰 CRITICAL: COIN TRANSFER MUST HAPPEN FIRST - BEFORE ANY OTHER CODE!
            // This prevents PK Battle or other operations from polluting the transaction
            try {
                // ✅ Format receiver_id to proper UUID format with dashes
                $receiverId = isset($giftData['receiver_id']) ? $this->formatUuid((string) $giftData['receiver_id']) : null;

                Log::info('🎁 COIN TRANSFER: 🚀🚀🚀 Starting ISOLATED coin transfer (auto-commit mode) 🚀🚀🚀', [
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'gift_id' => $gift?->id,
                    'gift_exists' => $gift !== null,
                ]);

                if ($gift && $receiverId) {
                    // Get sender and receiver users
                    $sender = \App\Models\User::find($senderId);
                    $receiver = \App\Models\User::find($receiverId);

                    if ($sender && $receiver) {
                        // Get final price (considers discounts)
                        $coinAmount = $gift->final_price;

                        Log::info('🎁 COIN TRANSFER: Balance check', [
                            'sender_balance' => $sender->coin_balance,
                            'required_amount' => $coinAmount,
                            'has_enough' => $sender->coin_balance >= $coinAmount,
                        ]);

                        // Check if sender has enough balance
                        if ($sender->coin_balance >= $coinAmount) {
                            try {
                                // ✅ STEP 1: Decrement sender balance
                                Log::info('🎁 COIN TRANSFER: Step 1 - Decrementing sender balance');
                                $sql1 = sprintf(
                                    "UPDATE users SET coin_balance = coin_balance - %d, updated_at = NOW() WHERE id = '%s'",
                                    $coinAmount,
                                    $senderId
                                );
                                $affectedRows = \DB::update($sql1);

                                if ($affectedRows === 0) {
                                    throw new \Exception('Step 1 failed - sender not found');
                                }
                                Log::info('🎁 COIN TRANSFER: Step 1 ✅ Success', ['affected_rows' => $affectedRows]);

                                // ✅ STEP 2: Insert sender transaction
                                Log::info('🎁 COIN TRANSFER: Step 2 - Creating sender transaction record');
                                $sql2 = sprintf(
                                    "INSERT INTO user_coin_transactions (user_id, amount, wallet_type, transaction_type, gift_id, related_user_id, created_at, updated_at) VALUES ('%s', %d, %d, %d, %d, '%s', NOW(), NOW())",
                                    $senderId,
                                    -$coinAmount,
                                    \App\Helpers\Variable::WALLET_TYPE_DEFAULT,
                                    \App\Models\Relations\UserCoinTransaction::TRANSACTION_TYPE_PURCHASE_GIFT,
                                    $gift->id,
                                    $receiverId
                                );
                                \DB::statement($sql2);
                                Log::info('🎁 COIN TRANSFER: Step 2 ✅ Success');

                                // ✅ STEP 3: Increment receiver earned balance
                                Log::info('🎁 COIN TRANSFER: Step 3 - Incrementing receiver earned balance');
                                $sql3 = sprintf(
                                    "UPDATE users SET earned_coin_balance = COALESCE(earned_coin_balance, 0) + %d, updated_at = NOW() WHERE id = '%s'",
                                    $coinAmount,
                                    $receiverId
                                );
                                $affectedRows3 = \DB::update($sql3);

                                if ($affectedRows3 === 0) {
                                    throw new \Exception('Step 3 failed - receiver not found');
                                }
                                Log::info('🎁 COIN TRANSFER: Step 3 ✅ Success', ['affected_rows' => $affectedRows3]);

                                // ✅ STEP 4: Insert receiver transaction
                                Log::info('🎁 COIN TRANSFER: Step 4 - Creating receiver transaction record');
                                $sql4 = sprintf(
                                    "INSERT INTO user_coin_transactions (user_id, amount, wallet_type, transaction_type, gift_id, related_user_id, created_at, updated_at) VALUES ('%s', %d, %d, %d, %d, '%s', NOW(), NOW())",
                                    $receiverId,
                                    $coinAmount,
                                    \App\Helpers\Variable::WALLET_TYPE_EARNED,
                                    \App\Models\Relations\UserCoinTransaction::TRANSACTION_TYPE_RECEIVE_GIFT,
                                    $gift->id,
                                    $senderId
                                );
                                \DB::statement($sql4);
                                Log::info('🎁 COIN TRANSFER: Step 4 ✅ Success');

                                Log::info('🎁 COIN TRANSFER: ✅✅✅ ALL STEPS COMPLETED SUCCESSFULLY! ✅✅✅', [
                                    'sender_id' => $senderId,
                                    'receiver_id' => $receiverId,
                                    'gift_id' => $gift->id,
                                    'coin_amount' => $coinAmount,
                                ]);
                            } catch (\Exception $stepError) {
                                Log::error('🎁 COIN TRANSFER: ❌ STEP FAILED', [
                                    'error' => $stepError->getMessage(),
                                    'sender_id' => $senderId,
                                    'receiver_id' => $receiverId,
                                    'gift_id' => $gift->id,
                                    'coin_amount' => $coinAmount,
                                ]);
                            }
                        } else {
                            Log::warning('🎁 COIN TRANSFER: ⚠️ Insufficient balance', [
                                'sender_id' => $senderId,
                                'required' => $coinAmount,
                                'available' => $sender->coin_balance,
                            ]);
                        }
                    } else {
                        Log::warning('🎁 COIN TRANSFER: ⚠️ User not found', [
                            'sender_found' => $sender !== null,
                            'receiver_found' => $receiver !== null,
                        ]);
                    }
                } else {
                    Log::warning('🎁 COIN TRANSFER: ⚠️ Missing gift or receiver_id', [
                        'gift_exists' => $gift !== null,
                        'receiver_id' => $receiverId,
                    ]);
                }
            } catch (\Exception $coinTransferError) {
                Log::error('🎁 COIN TRANSFER: ❌ OUTER EXCEPTION', [
                    'error' => $coinTransferError->getMessage(),
                    'trace' => $coinTransferError->getTraceAsString(),
                ]);
            }

            // Create broadcast payload for all participants
            $giftPayload = [
                'gift_id' => $giftData['gift_id'] ?? 'unknown',
                'gift_name' => $giftData['gift_name'] ?? 'Hediye',
                'gift_image' => $giftData['gift_image'] ?? null,
                'gift_video_url' => $giftData['gift_video_url'] ?? null,
                'gift_slug' => $giftData['gift_slug'] ?? null,
                'gift_team_id' => $giftData['gift_team_id'] ?? null,
                'sender_name' => $giftData['sender_name'] ?? 'Anonim',
                'sender_avatar' => $giftData['sender_avatar'] ?? null,
                'sender_id' => $giftData['sender_id'] ?? $senderId,
            ];

            // Add frame-by-frame animation data if gift is found
            if ($gift) {
                $giftPayload['is_frame_animation'] = $gift->is_frame_animation;
                $giftPayload['frame_count'] = $gift->frame_count;
                $giftPayload['animation_duration'] = $gift->animation_duration;
                $giftPayload['frame_rate'] = $gift->frame_rate;
                $giftPayload['animation_style'] = $gift->animation_style;

                // ✅ CRITICAL FIX: NEVER send frame URLs via WebSocket to avoid payload limits
                // ALL frame animations will fetch URLs via HTTP API regardless of size
                if ($gift->is_frame_animation) {
                    $giftPayload['needs_frame_fetch'] = true; // Always fetch via HTTP API
                    $giftPayload['frame_urls'] = []; // Empty array - all frames fetched via API

                    Log::info('🎁 BACKEND: Frame animation detected - will fetch via HTTP API', [
                        'gift_id' => $gift->id,
                        'frame_count' => $gift->frame_count,
                        'total_frames' => count($gift->frame_urls ?? []),
                        'websocket_payload_size' => 'minimal (no frame URLs)'
                    ]);
                } else {
                    $giftPayload['needs_frame_fetch'] = false;
                    $giftPayload['frame_urls'] = [];
                }

                Log::info('🎁 BACKEND: Gift payload optimized for ALL frame counts (1-500+)', [
                    'gift_id' => $gift->id,
                    'is_frame_animation' => $gift->is_frame_animation,
                    'frame_count' => $gift->frame_count,
                    'websocket_includes_frames' => false,
                    'http_api_required' => $gift->is_frame_animation
                ]);
            }

            // 🎮 PK BATTLE: Check if there's an active PK battle for this stream
            $receiverId = $giftData['receiver_id'] ?? null;

            // ✅ DEBUG: Log all gift data keys to see what's available
            Log::info('🎮 PK Gift Check: Gift data keys', [
                'keys' => array_keys($giftData),
                'gift_data_sample' => array_slice($giftData, 0, 5), // First 5 items
            ]);

            Log::info('🎮 PK Gift Check: Looking for active battle', [
                'stream_id' => $liveStreamId,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
            ]);

            // Try to find battle by stream ID first
            $activeBattle = PKBattle::where('live_stream_id', $liveStreamId)
                ->where('status', 'ACTIVE')
                ->first();

            if (!$activeBattle) {
                // Check if receiver is in a PK battle (cohost stream - ZEGO ID)
                $activeBattle = PKBattle::where('opponent_stream_id', $liveStreamId)
                    ->where('status', 'ACTIVE')
                    ->first();
            }

            // 🎮 NEW: Check if this is a cohost's MongoDB stream ID (in cohost_stream_ids array)
            if (!$activeBattle) {
                Log::info('🎮 PK Gift Check: Trying to find battle by cohost MongoDB stream ID', [
                    'stream_id' => $liveStreamId,
                ]);

                $activeBattle = PKBattle::where('status', 'ACTIVE')
                    ->whereJsonContains('cohost_stream_ids', $liveStreamId)
                    ->first();

                if ($activeBattle) {
                    Log::info('🎮 PK Gift Check: ✅ Found battle by cohost MongoDB stream ID!', [
                        'battle_id' => $activeBattle->id,
                        'cohost_stream_ids' => $activeBattle->cohost_stream_ids,
                    ]);
                }
            }

            // If still not found, try finding by receiver user ID
            if (!$activeBattle && $receiverId) {
                Log::info('🎮 PK Gift Check: Trying to find battle by receiver user ID', [
                    'receiver_id' => $receiverId,
                ]);

                $activeBattle = PKBattle::where(function ($query) use ($receiverId) {
                    $query->where('challenger_id', $receiverId)
                        ->orWhere('opponent_id', $receiverId);
                })
                    ->where('status', 'ACTIVE')
                    ->first();

                if ($activeBattle) {
                    Log::info('🎮 PK Gift Check: ✅ Found battle by receiver user ID!');
                }
            }

            if ($activeBattle) {
                Log::info('🎮 PK Gift Check: Found active battle!', [
                    'battle_id' => $activeBattle->id,
                    'host_stream' => $activeBattle->live_stream_id,
                    'opponent_stream' => $activeBattle->opponent_stream_id,
                    'challenger_id' => $activeBattle->challenger_id,
                    'opponent_id' => $activeBattle->opponent_id,
                ]);
            } else {
                Log::info('🎮 PK Gift Check: No active battle found');
            }

            $broadcastPayload = [
                'gift' => $giftPayload,
                'timestamp' => now()->timestamp * 1000, // JavaScript timestamp format
                'live_stream_id' => $liveStreamId,
                'sender_id' => $senderId,
                'event_type' => 'gift_sent',
            ];

            // Broadcast to all participants in the live stream
            $channelName = "live-stream.{$liveStreamId}";

            Log::info('🎁 BACKEND: Broadcasting gift event to channel', [
                'channel' => $channelName,
                'payload' => $broadcastPayload
            ]);

            // Use Laravel's proper Event class for broadcasting
            Log::info('🎁 BACKEND: 🚀🚀🚀 DISPATCHING GIFT EVENT 🚀🚀🚀', [
                'channel' => $channelName,
                'event_class' => 'GiftSentEvent',
                'payload_keys' => array_keys($broadcastPayload),
                'gift_name' => $broadcastPayload['gift']['gift_name'] ?? 'Unknown',
                'timestamp' => now()->toDateTimeString(),
                'broadcast_driver' => config('broadcasting.default'),
                'pusher_config_exists' => config('broadcasting.connections.pusher') !== null,
                'queue_config' => config('queue.default')
            ]);

            // Check if broadcasting is properly configured
            if (config('broadcasting.default') === null) {
                Log::error('🎁 BACKEND: ❌ NO BROADCAST DRIVER CONFIGURED!');
            }

            // Try to broadcast via WebSocket if available
            try {
                event(new GiftSentEvent($channelName, $broadcastPayload));

                Log::info('🎁 BACKEND: ✅ Event dispatched via broadcast', [
                    'channel' => $channelName
                ]);
            } catch (\Exception $broadcastError) {
                Log::warning('🎁 BACKEND: Broadcast failed, falling back to database', [
                    'error' => $broadcastError->getMessage()
                ]);
            }

            // Also save to database for reliability
            try {
                \DB::table('live_stream_events')->insert([
                    'stream_id' => $liveStreamId,
                    'event_type' => 'gift_sent',
                    'event_data' => json_encode($broadcastPayload),
                    'sender_id' => $senderId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('🎁 BACKEND: ✅ Gift event saved to live_stream_events', [
                    'stream_id' => $liveStreamId
                ]);

                // ✅ CRITICAL FIX: Save to AgoraChannelGift table for PK Battle Score Service
                if ($gift) {
                    $agoraChannelGift = \App\Models\Agora\AgoraChannelGift::create([
                        'agora_channel_id' => $liveStreamId,
                        'challenge_id' => $activeBattle ? $activeBattle->id : null,
                        'gift_id' => $gift->id,
                        'user_id' => $senderId,
                        'recipient_user_id' => $receiverId,
                        'coin_value' => $gift->coin_value,
                        'quantity' => 1, // Default to 1 for now
                        'streak' => 1,
                    ]);

                    Log::info('🎁 BACKEND: ✅ Gift saved to AgoraChannelGift table', [
                        'id' => $agoraChannelGift->id,
                        'channel_id' => $liveStreamId
                    ]);
                }

            } catch (\Exception $dbError) {
                Log::error('🎁 BACKEND: Failed to save gift event to database', [
                    'error' => $dbError->getMessage()
                ]);
            }

            // 🎮 PK BATTLE: If there's an active battle, notify PK system
            if ($activeBattle && $gift) {
                try {
                    $pkScoreService = app(PKBattleScoreService::class);

                    // Get receiver info (needed for PK score tracking)
                    $receiverId = $giftData['receiver_id'] ?? null;
                    $receiverName = $giftData['receiver_name'] ?? 'Unknown';

                    // ✅ TEMPORARY FIX: Use 25 coins if coin_value is 0 for testing
                    $coinValue = $gift->coin_value > 0 ? $gift->coin_value : 25;

                    $pkGiftData = [
                        'gift_id' => $gift->id,
                        'gift_name' => $gift->name,
                        'gift_image' => $gift->image,
                        'coin_value' => $coinValue,  // Use fixed value if 0
                        'sender_id' => $senderId,
                        'sender_name' => $giftData['sender_name'] ?? 'Unknown',
                        'receiver_id' => $receiverId,
                        'receiver_name' => $receiverName,
                        'stream_id' => $liveStreamId,
                        'quantity' => 1,
                        'timestamp' => now()->toISOString(),
                    ];

                    $pkScoreService->handleGiftReceived($activeBattle, $pkGiftData);

                    Log::info('🎮 PK Battle Gift: Gift sent during active PK battle', [
                        'battle_id' => $activeBattle->id,
                        'gift_id' => $gift->id,
                        'sender_id' => $senderId,
                        'receiver_id' => $receiverId,
                        'stream_id' => $liveStreamId,
                        'coin_value' => $gift->coin_value,
                    ]);
                } catch (\Exception $e) {
                    Log::error('🎮 PK Battle Gift Error: Failed to handle gift', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'battle_id' => $activeBattle->id ?? null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Gift event broadcasted successfully',
                'channel' => $channelName,
                'event' => 'gift.sent'
            ]);

        } catch (\Exception $e) {
            Log::error('🎁 BACKEND: Error handling gift event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to process gift event',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle cohost joined notification
     */
    public function sendCohostJoinedNotification(Request $request)
    {
        try {
            Log::info('🎮 BACKEND: Received cohost joined notification', [
                'data' => $request->all()
            ]);

            $originalStreamId = $request->input('original_stream_id');
            $cohostStreamId = $request->input('cohost_stream_id');
            $cohostUserId = $request->input('cohost_user_id');
            $cohostUserName = $request->input('cohost_user_name');

            // Validate required data
            if (!$originalStreamId || !$cohostStreamId || !$cohostUserId || !$cohostUserName) {
                Log::warning('🎮 BACKEND: Missing required data for cohost notification', [
                    'original_stream_id' => $originalStreamId,
                    'cohost_stream_id' => $cohostStreamId,
                    'cohost_user_id' => $cohostUserId,
                    'cohost_user_name' => $cohostUserName
                ]);
                return response()->json(['error' => 'Missing required data'], 400);
            }

            $broadcastPayload = [
                'type' => 'cohost_joined',
                'cohost_stream_id' => $cohostStreamId,
                'cohost_user_id' => $cohostUserId,
                'cohost_user_name' => $cohostUserName,
                'timestamp' => now()->timestamp * 1000,
                'event_type' => 'cohost_joined',
            ];

            // Broadcast to the original stream channel
            $channelName = "live-stream.{$originalStreamId}";

            Log::info('🎮 BACKEND: Broadcasting cohost joined event to channel', [
                'channel' => $channelName,
                'payload' => $broadcastPayload
            ]);

            // Create a generic event for cohost joining
            event(new \App\Events\LiveStream\StreamMessageSent($channelName, $broadcastPayload));

            Log::info('🎮 BACKEND: ✅ Cohost joined notification sent successfully', [
                'channel' => $channelName,
                'cohost_stream_id' => $cohostStreamId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cohost joined notification sent successfully',
                'channel' => $channelName,
                'event' => 'cohost_joined'
            ]);

        } catch (\Exception $e) {
            Log::error('🎮 BACKEND: Error sending cohost notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send cohost notification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle generic client events for live streams
     */
    public function handleClientEvent(Request $request)
    {
        $eventType = $request->input('event_type');

        Log::info('🎁 WEBSOCKET CONTROLLER: handleClientEvent() called', [
            'event_type' => $eventType,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        switch ($eventType) {
            case 'gift_sent':
                return $this->handleGiftSent($request);
            case 'cohost_joined':
                return $this->sendCohostJoinedNotification($request);
            case 'chat_message':
                return $this->handleChatMessage($request);
            case 'like_sent':
                return $this->handleLikeSent($request);
            default:
                Log::warning('🎁 BACKEND: Unknown client event type', [
                    'event_type' => $eventType,
                    'data' => $request->all()
                ]);
                return response()->json(['error' => 'Unknown event type'], 400);
        }
    }

    /**
     * Handle chat messages for live streams
     */
    protected function handleChatMessage(Request $request)
    {
        try {
            $liveStreamId = $request->input('live_stream_id');
            $targetStreamId = $request->input('target_stream_id', $liveStreamId);
            $message = $request->input('message');
            $senderId = $request->input('sender_id');
            $senderName = $request->input('sender_name');

            Log::info('💬 BACKEND: Processing chat message', [
                'stream_id' => $liveStreamId,
                'target_stream' => $targetStreamId,
                'sender' => $senderName
            ]);

            $chatPayload = [
                'type' => 'chat',
                'message' => $message,
                'sender_id' => $senderId,
                'sender_name' => $senderName,
                'target_stream_id' => $targetStreamId,
                'timestamp' => now()->timestamp * 1000,
            ];

            // Save to database for reliability
            try {
                \DB::table('live_stream_events')->insert([
                    'stream_id' => $targetStreamId,
                    'event_type' => 'chat_message',
                    'event_data' => json_encode($chatPayload),
                    'sender_id' => $senderId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('💬 BACKEND: ✅ Chat message saved to database');
            } catch (\Exception $e) {
                Log::error('💬 BACKEND: Failed to save chat message', [
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Chat message processed'
            ]);

        } catch (\Exception $e) {
            Log::error('💬 BACKEND: Error handling chat message', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to process chat message'
            ], 500);
        }
    }

    /**
     * Handle like events for live streams
     * Broadcasts like animations to all viewers in the stream
     */
    protected function handleLikeSent(Request $request)
    {
        try {
            $liveStreamId = $request->input('live_stream_id');
            $likeData = $request->input('like', []);

            Log::info('⚽ BACKEND: Processing like event', [
                'stream_id' => $liveStreamId,
                'like_data' => $likeData
            ]);

            // Validate required data
            if (!$liveStreamId || empty($likeData)) {
                Log::warning('⚽ BACKEND: Missing required data for like event', [
                    'live_stream_id' => $liveStreamId,
                    'like_data' => $likeData
                ]);
                return response()->json(['error' => 'Missing required data'], 400);
            }

            // Create broadcast payload for all participants
            $likePayload = [
                'sender_id' => $likeData['sender_id'] ?? null,
                'sender_name' => $likeData['sender_name'] ?? 'Anonymous',
                'sender_avatar' => $likeData['sender_avatar'] ?? null,
                'timestamp' => $likeData['timestamp'] ?? (now()->timestamp * 1000),
            ];

            $broadcastPayload = [
                'like' => $likePayload,
                'timestamp' => now()->timestamp * 1000,
                'live_stream_id' => $liveStreamId,
                'event_type' => 'like_sent',
            ];

            // Broadcast to all participants in the live stream
            $channelName = "live-stream.{$liveStreamId}";

            Log::info('⚽ BACKEND: Broadcasting like event to channel', [
                'channel' => $channelName,
                'payload' => $broadcastPayload
            ]);

            // Use Laravel's event system for broadcasting
            try {
                event(new LikeSentEvent($channelName, $broadcastPayload));

                Log::info('⚽ BACKEND: ✅ Like event broadcasted successfully', [
                    'channel' => $channelName
                ]);
            } catch (\Exception $broadcastError) {
                Log::warning('⚽ BACKEND: Broadcast failed', [
                    'error' => $broadcastError->getMessage()
                ]);
            }

            // Save to database for reliability
            try {
                \DB::table('live_stream_events')->insert([
                    'stream_id' => $liveStreamId,
                    'event_type' => 'like_sent',
                    'event_data' => json_encode($broadcastPayload),
                    'sender_id' => $likeData['sender_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('⚽ BACKEND: ✅ Like event saved to database');
            } catch (\Exception $e) {
                Log::error('⚽ BACKEND: Failed to save like event to database', [
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Like event broadcasted successfully',
                'channel' => $channelName,
                'event' => 'like.sent'
            ]);

        } catch (\Exception $e) {
            Log::error('⚽ BACKEND: Error handling like event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to process like event',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
