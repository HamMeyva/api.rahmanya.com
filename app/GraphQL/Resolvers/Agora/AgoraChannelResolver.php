<?php

namespace App\GraphQL\Resolvers\Agora;

use App\Models\User;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraChannelResolver
{
    /**
     * Resolve user for AgoraChannel
     */
    public function resolveUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Root value can be array or object
        $userId = null;

        if (is_array($rootValue)) {
            $userId = $rootValue['user_id'] ?? null;
        } elseif (is_object($rootValue)) {
            $userId = $rootValue->user_id ?? null;
        }

        if (!$userId) {
            return null;
        }

        try {
            return User::find($userId);
        } catch (\Exception $e) {
            \Log::error('AgoraChannelResolver::resolveUser error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Resolve status for AgoraChannel
     */
    public function resolveStatus($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Root value can be array or object
        $statusId = null;

        if (is_array($rootValue)) {
            $statusId = $rootValue['status_id'] ?? null;
        } elseif (is_object($rootValue)) {
            $statusId = $rootValue->status_id ?? null;
        }

        // Map status_id to status string
        switch ($statusId) {
            case 1:
                return 'waiting';
            case 2:
                return 'live';
            case 3:
                return 'ended';
            case 4:
                return 'banned';
            default:
                return 'unknown';
        }
    }

    /**
     * Resolve parent channel for cohost streams
     */
    public function resolveParentChannel($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Root value can be array or object
        $parentChannelId = null;

        if (is_array($rootValue)) {
            $parentChannelId = $rootValue['parent_channel_id'] ?? null;
        } elseif (is_object($rootValue)) {
            $parentChannelId = $rootValue->parent_channel_id ?? null;
        }

        if (!$parentChannelId) {
            return null;
        }

        try {
            return \App\Models\Agora\AgoraChannel::find($parentChannelId);
        } catch (\Exception $e) {
            \Log::error('AgoraChannelResolver::resolveParentChannel error', [
                'parent_channel_id' => $parentChannelId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Resolve cohost channels for host streams
     */
    public function resolveCohostChannels($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            // Get cohost channel IDs from root value
            $cohostChannelIds = null;

            if (is_array($rootValue)) {
                $cohostChannelIds = $rootValue['cohost_channel_ids'] ?? [];
            } elseif (is_object($rootValue)) {
                $cohostChannelIds = $rootValue->cohost_channel_ids ?? [];
            }

            if (empty($cohostChannelIds)) {
                return [];
            }

            // Fetch all cohost channels
            $cohostChannels = \App\Models\Agora\AgoraChannel::whereIn('_id', $cohostChannelIds)
                ->where('is_online', true)
                ->get();

            \Log::info('AgoraChannelResolver::resolveCohostChannels', [
                'cohost_ids' => $cohostChannelIds,
                'found_count' => $cohostChannels->count()
            ]);

            return $cohostChannels;
        } catch (\Exception $e) {
            \Log::error('AgoraChannelResolver::resolveCohostChannels error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
