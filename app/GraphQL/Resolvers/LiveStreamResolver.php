<?php

namespace App\GraphQL\Resolvers;

use App\Models\LiveStream;
use App\Models\LiveStreamParticipant;
use App\Services\LiveStream\StreamRecoveryService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Log;

class LiveStreamResolver
{
    public $withinTransaction = false; // Disable automatic transaction wrapping

    public function active($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return LiveStream::with(['host', 'participants.user'])
            ->where('status', 'active')
            ->when(isset($args['category']), function ($query) use ($args) {
                return $query->where('category', $args['category']);
            })
            ->orderByDesc('viewer_count')
            ->get();
    }

    public function find($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return LiveStream::with(['host', 'participants.user', 'pkBattles'])
            ->findOrFail($args['id']);
    }

    public function participants($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return LiveStreamParticipant::with('user')
            ->where('live_stream_id', $rootValue->id)
            ->where('is_active', true)
            ->orderBy('joined_at')
            ->get();
    }

    public function zegoRoomId($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $rootValue->zego_room_id ?? $rootValue->agora_channel_id;
    }

    public function zegoEnabled($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $rootValue->zego_enabled ?? true;
    }

    public function recentGifts($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $rootValue->giftTransactions()
            ->with(['sender', 'gift'])
            ->latest('sent_at')
            ->limit(50)
            ->get();
    }

    /**
     * Keep co-host stream alive
     */
    public function keepCohostAlive($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $recoveryService = new StreamRecoveryService();
            $success = $recoveryService->keepCohostStreamAlive(
                $args['cohost_stream_id'],
                $args['host_stream_id']
            );

            Log::info('Keep co-host alive called', [
                'cohost_stream_id' => $args['cohost_stream_id'],
                'host_stream_id' => $args['host_stream_id'],
                'success' => $success
            ]);

            return [
                'success' => $success,
                'message' => $success ? 'Co-host stream kept alive' : 'Failed to keep co-host stream alive'
            ];
        } catch (\Exception $e) {
            Log::error('Error keeping co-host stream alive', [
                'error' => $e->getMessage(),
                'cohost_stream_id' => $args['cohost_stream_id'] ?? null
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recover offline streams in a room
     */
    public function recoverRoomStreams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            $recoveryService = new StreamRecoveryService();
            $success = $recoveryService->monitorAndRecover($args['room_id']);

            return [
                'success' => $success,
                'message' => $success ? 'Streams recovered' : 'Failed to recover streams'
            ];
        } catch (\Exception $e) {
            Log::error('Error recovering room streams', [
                'error' => $e->getMessage(),
                'room_id' => $args['room_id'] ?? null
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
