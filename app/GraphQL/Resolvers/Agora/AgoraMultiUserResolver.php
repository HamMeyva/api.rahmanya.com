<?php

namespace App\GraphQL\Resolvers\Agora;

use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelInvite;
use App\Services\LiveStream\AgoraChannelInviteService;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraMultiUserResolver
{
    public function inviteUserToStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $channelName = $args['channelName'];
        $userId = $args['userId'];
        
        try {
            $channel = AgoraChannel::where('id', $channelName)->orWhere('channel_name', $channelName)->firstOrFail();

            if ($channel->user_id !== $user->id) {
                throw new \Exception('Bu yayına sadece sahibi davet gönderebilir');
            }

            // Multi-guest mode'u aktif yap
            if (!$channel->isMultiGuestMode()) {
                $channel->update([
                    'mode' => 'multi_guest',
                    'max_participants' => 6
                ]);
            }

            // LiveStreamParticipant oluştur
            $participant = \App\Models\LiveStreamParticipant::updateOrCreate(
                [
                    'live_stream_id' => $channel->id,
                    'user_id' => $userId,
                ],
                [
                    'role' => 'guest',
                    'participant_type' => 'co_host',
                    'is_active' => false, // Davet kabul edilince true olacak
                    'joined_at' => now(),
                ]
            );

            // Daveti Mongo tarafında oluştur
            /** @var AgoraChannelInviteService $inviteService */
            $inviteService = app(AgoraChannelInviteService::class);
            $invite = $inviteService->inviteUserToChannel([
                'agora_channel_id' => $channel->id,
                'invited_user_id' => $userId,
            ], $user);

            return [
                'success' => true,
                'message' => 'Kullanıcı yayına davet edildi',
                'invite_id' => $invite ? (string) ($invite->_id ?? $invite->id ?? '') : '',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'invite_id' => null,
            ];
        }
    }
    
    public function acceptStreamInvite($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $channelName = $args['channelName'];
        $inviterId = $args['inviterId'];
        
        try {
            $channel = AgoraChannel::where('id', $channelName)->orWhere('channel_name', $channelName)->firstOrFail();

            // Check if stream is active - either LIVE status or is_online flag
            if ($channel->status_id !== AgoraChannel::STATUS_LIVE && !$channel->is_online) {
                return [
                    'success' => false,
                    'message' => 'Yayın aktif değil.',
                    'token' => null,
                    'channel_name' => null,
                    'broadcaster_id' => null,
                    'cohost_stream_id' => null,
                    'cohost_chat_channel_id' => null,
                    'cohost_stream_key' => null,
                ];
            }

            // Try to find existing participant or create new one
            $participant = \App\Models\LiveStreamParticipant::where('live_stream_id', $channel->id)
                ->where('user_id', $user->id)
                ->first();
            
            if ($participant) {
                $participant->update(['is_active' => true]);
            } else {
                // Create new participant record if it doesn't exist
                $participant = \App\Models\LiveStreamParticipant::create([
                    'live_stream_id' => $channel->id,
                    'user_id' => $user->id,
                    'role' => 'guest',
                    'participant_type' => 'co_host',
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            // For Zego auto-mixing: DON'T create a separate stream
            // Co-host publishes to the SAME room as the host
            // Zego will automatically mix both streams

            /** @var \App\Services\LiveStream\AgoraChannelService $agoraChannelService */
            $agoraChannelService = app(\App\Services\LiveStream\AgoraChannelService::class);
            $result = $agoraChannelService->joinAsCohost($channel, $user);

            // With Zego auto-mixing, no need for manual mixer configuration
            // Both host and co-host publish to the same room
            // Zego automatically creates the mixed layout

            \Illuminate\Support\Facades\Log::info('🎮 ZEGO AUTO-MIX: Co-host joining same room', [
                'room_id' => $channel->channel_name,
                'host_id' => $channel->user_id,
                'cohost_id' => $user->id,
                'note' => 'Zego will automatically mix streams in the same room'
            ]);

            // Notify the host that a cohost has joined
            try {
                // Send WebSocket notification to the host
                \Illuminate\Support\Facades\Http::post(url('/api/v1/live-stream/websocket/notify'), [
                    'stream_id' => $channel->id,
                    'type' => 'cohost_joined',
                    'cohost_user_id' => $user->id,
                    'cohost_user_name' => $user->nickname ?? $user->name ?? 'Cohost',
                ]);

                \Illuminate\Support\Facades\Log::info('🎮 COHOST-NOTIFICATION: Sent cohost joined notification to host', [
                    'original_stream_id' => $channel->id,
                    'cohost_user' => $user->nickname ?? $user->name,
                ]);
            } catch (\Exception $notifyError) {
                \Illuminate\Support\Facades\Log::error('🎮 COHOST-NOTIFICATION: Failed to notify host', [
                    'error' => $notifyError->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'message' => 'Yayına cohost olarak katıldınız',
                'token' => $result['token'],
                'channel_name' => $result['channel_name'],
                'broadcaster_id' => $channel->user_id,
                'agora_channel_id' => $result['agora_channel_id'],
                'agora_uid' => $result['agora_uid'],
                // Same room as host - no separate stream
                'cohost_stream_id' => $channel->id,
                'cohost_chat_channel_id' => 'chat_' . $channel->channel_name,
                'cohost_stream_key' => null, // Using same stream as host
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'token' => null,
                'channel_name' => null,
                'broadcaster_id' => null,
            ];
        }
    }
    
    public function rejectStreamInvite($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $user = $context->user();
        $channelName = $args['channelName'];
        
        try {
            $channel = AgoraChannel::where('id', $channelName)->orWhere('channel_name', $channelName)->firstOrFail();
            
            LiveStreamParticipant::where('live_stream_id', $channel->id)
                ->where('user_id', $user->id)
                ->delete();
            
            return [
                'success' => true,
                'message' => 'Davet reddedildi',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    public function removeUserFromStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): bool
    {
        $user = $context->user();
        $channelName = $args['channelName'];
        $userId = $args['userId'];
        
        try {
            $channel = AgoraChannel::where('id', $channelName)->orWhere('channel_name', $channelName)->firstOrFail();
            
            if ($channel->user_id !== $user->id) {
                throw new \Exception('Bu yayından sadece sahibi kullanıcı çıkarabilir');
            }
            
            LiveStreamParticipant::where('live_stream_id', $channel->id)
                ->where('user_id', $userId)
                ->delete();
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function pendingStreamInvites($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        
        return LiveStreamParticipant::with(['liveStream', 'user'])
            ->where('user_id', $user->id)
            ->where('is_active', false)
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'stream_id' => $participant->live_stream_id,
                    'inviter_id' => $participant->liveStream?->user_id ?? null,
                    'invitee_id' => $participant->user_id,
                    'status' => 'pending',
                    'created_at' => $participant->created_at,
                    'updated_at' => $participant->updated_at,
                ];
            });
    }
    
    public function streamParticipants($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $channelName = $args['channelName'];

        // Log the incoming request
        \Illuminate\Support\Facades\Log::info('🔍 streamParticipants query', [
            'channelName' => $channelName,
            'user_id' => $context->user()?->id ?? 'guest',
        ]);

        // Try to find by ID first (MongoDB ObjectId), then by channel_name
        $channel = AgoraChannel::where('id', $channelName)
            ->orWhere('channel_name', $channelName)
            ->first();

        if (!$channel) {
            \Illuminate\Support\Facades\Log::warning('🔍 streamParticipants: Channel not found', [
                'channelName' => $channelName,
            ]);
            return [];
        }

        \Illuminate\Support\Facades\Log::info('🔍 streamParticipants: Found channel', [
            'channel_id' => $channel->id,
            'channel_name' => $channel->channel_name,
            'is_online' => $channel->is_online,
            'status_id' => $channel->status_id,
        ]);

        // Fetch active participants from SQL table with user data
        $participants = \App\Models\LiveStreamParticipant::with('user')
            ->where('live_stream_id', (string)$channel->id)
            ->where('is_active', true)
            ->get();

        \Illuminate\Support\Facades\Log::info('🔍 streamParticipants: Found participants', [
            'channel_id' => $channel->id,
            'total_participants' => $participants->count(),
            'participants' => $participants->map(function ($p) {
                return [
                    'user_id' => $p->user_id,
                    'role' => $p->role,
                    'participant_type' => $p->participant_type,
                    'is_active' => $p->is_active,
                ];
            }),
        ]);

        // Return the Eloquent models directly, not arrays
        // The GraphQL schema will handle the field resolution
        return $participants;
    }
}
