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
                'agora_channel' => $respondToInviteResponse['agora_channel'] ?? null
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
        
        $invites = AgoraChannelInvite::where('invited_user_id', $authUser->id)->orderByDesc('created_at');

        return [
            'success' => true,
            'data' => $invites->paginate($limit, ['*'], 'page', $page)
        ];
    }
}
