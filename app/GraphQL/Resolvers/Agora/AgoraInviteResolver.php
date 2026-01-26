<?php

namespace App\GraphQL\Resolvers\Agora;

use Exception;
use App\Models\Agora\AgoraChannelInvite;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\LiveStream\AgoraChannelInviteService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraInviteResolver
{
    protected $inviteService;

    public function __construct(AgoraChannelInviteService $inviteService)
    {
        $this->inviteService = $inviteService;
    }

    public function inviteGuestToStream($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'agora_channel_id' => 'required',
            'invited_user_id' => 'required',
        ], [
            'agora_channel_id.required' => __('validation.required', ['attribute' => 'Canlı yayın']),
            'invited_user_id.required' => __('validation.required', ['attribute' => 'Konuk']),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $invite = $this->inviteService->inviteUserToChannel($args['input'], $authUser);

            return [
                'success' => true,
                'message' => 'Konuk davet edildi.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function respondToInvite($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'invite_id' => 'required',
            'response' => 'required|boolean',
        ], [
            'invite_id.required' => __('validation.required', ['attribute' => 'Davet']),
            'response.required' => __('validation.required', ['attribute' => 'Cevap']),
            'response.boolean' => __('validation.boolean', ['attribute' => 'Cevap']),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $respondToInviteResponse = $this->inviteService->respondToInvite($args['input'], $authUser);

            return [
                'success' => true,
                'message' => 'Davet başarıyla yanıtlandı.',
                'token' => $respondToInviteResponse['token'] ?? null,
                'agora_channel' => $respondToInviteResponse['agora_channel'] ?? null,
                'shared_video_room_id' => $respondToInviteResponse['shared_video_room_id'] ?? null,
                'parent_channel_id' => $respondToInviteResponse['parent_channel_id'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getMyStreamInvites($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];

        $authUser = $context->user();
        $limit = $input['limit'] ?? 10;
        $page = $input['page'] ?? 1;

        $invites = AgoraChannelInvite::where('invited_user_id', $authUser->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($invite) {
                // Build safe, plain array to avoid cross-connection relationship calls
                $channel = $invite->agoraChannel;
                $agoraChannelData = null;
                if ($channel) {
                    $agoraChannelData = [
                        'id' => $channel->id,
                        'channel_name' => $channel->channel_name ?? '',
                        'title' => $channel->title ?? ($channel->channel_name ?? 'Live'),
                        'is_online' => (bool) ($channel->is_online ?? false),
                    ];
                }

                // Manually hydrate user objects from SQL to avoid Mongo <-> SQL relation calls
                try {
                    $owner = \App\Models\User::query()->find($invite->user_id);
                } catch (\Throwable $e) {
                    $owner = null;
                }
                try {
                    $invited = \App\Models\User::query()->find($invite->invited_user_id);
                } catch (\Throwable $e) {
                    $invited = null;
                }

                $ownerData = $owner ? [
                    'id' => (string) $owner->id,
                    'name' => $owner->name ?? null,
                    'surname' => $owner->surname ?? null,
                    'nickname' => $owner->nickname ?? null,
                    'avatar' => $owner->avatar ?? null,
                ] : null;

                $invitedData = $invited ? [
                    'id' => (string) $invited->id,
                    'name' => $invited->name ?? null,
                    'surname' => $invited->surname ?? null,
                    'nickname' => $invited->nickname ?? null,
                    'avatar' => $invited->avatar ?? null,
                ] : null;

                return [
                    'id' => (string) $invite->id,
                    'agora_channel_id' => (string) $invite->agora_channel_id,
                    'user_id' => (string) $invite->user_id,
                    'invited_user_id' => (string) $invite->invited_user_id,
                    'status_id' => (int) $invite->status_id,
                    'get_status' => $invite->get_status ?? null,
                    'invited_at' => $invite->invited_at,
                    'responded_at' => $invite->responded_at,
                    'created_at' => $invite->created_at,
                    'updated_at' => $invite->updated_at,
                    'agora_channel_data' => $agoraChannelData,
                    'user' => $ownerData,
                    'invited_user' => $invitedData,
                ];
            })
            ->values();

        return [
            'success' => true,
            'data' => $invites
        ];
    }

    // Field resolvers to avoid cross-DB relationship calls
    public function resolveInviteUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // $rootValue can be an array or Eloquent model
        if (is_array($rootValue) && isset($rootValue['user'])) {
            return $rootValue['user'];
        }

        $userId = is_array($rootValue) ? ($rootValue['user_id'] ?? null) : ($rootValue->user_id ?? null);
        if (!$userId) {
            return null;
        }
        try {
            $u = \App\Models\User::query()->find($userId);
            return $u ? [
                'id' => (string) $u->id,
                'name' => $u->name ?? null,
                'surname' => $u->surname ?? null,
                'nickname' => $u->nickname ?? null,
                'avatar' => $u->avatar ?? null,
            ] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function resolveInviteInvitedUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        if (is_array($rootValue) && isset($rootValue['invited_user'])) {
            return $rootValue['invited_user'];
        }
        $userId = is_array($rootValue) ? ($rootValue['invited_user_id'] ?? null) : ($rootValue->invited_user_id ?? null);
        if (!$userId) {
            return null;
        }
        try {
            $u = \App\Models\User::query()->find($userId);
            return $u ? [
                'id' => (string) $u->id,
                'name' => $u->name ?? null,
                'surname' => $u->surname ?? null,
                'nickname' => $u->nickname ?? null,
                'avatar' => $u->avatar ?? null,
            ] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function resolveInviteChannelData($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        if (is_array($rootValue) && isset($rootValue['agora_channel_data'])) {
            return $rootValue['agora_channel_data'];
        }
        $channelId = is_array($rootValue) ? ($rootValue['agora_channel_id'] ?? null) : ($rootValue->agora_channel_id ?? null);
        if (!$channelId) {
            return null;
        }
        try {
            $channel = \App\Models\Agora\AgoraChannel::where('id', $channelId)
                ->orWhere('channel_name', $channelId)
                ->first();
            if (!$channel) {
                return null;
            }
            return [
                'id' => $channel->id,
                'channel_name' => $channel->channel_name ?? '',
                'title' => $channel->title ?? ($channel->channel_name ?? 'Live'),
                'is_online' => (bool) ($channel->is_online ?? false),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    // Frontend compatibility methods
    public function streamInvitations($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Alias for getMyStreamInvites to match frontend expectations
        return $this->getMyStreamInvites($rootValue, $args, $context, $resolveInfo);
    }

    public function sendStreamInvitation($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        // Alias for inviteGuestToStream to match frontend expectations  
        return $this->inviteGuestToStream($rootValue, $args, $context, $resolveInfo);
    }

    /**
     * Davet edilebilir kullanıcıları listeler
     */
    public function getInvitableUsers($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $agoraChannelId = $args['agora_channel_id'];

        try {
            $invitableUsers = $this->inviteService->getInvitableUsers($agoraChannelId, $authUser);

            return [
                'success' => true,
                'users' => $invitableUsers,
                'total' => count($invitableUsers)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'users' => [],
                'total' => 0
            ];
        }
    }
}
