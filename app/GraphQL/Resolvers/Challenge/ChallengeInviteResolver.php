<?php

namespace App\GraphQL\Resolvers\Challenge;

use Exception;
use Illuminate\Support\Facades\Log;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Services\Challenges\ChallengeService;

class ChallengeInviteResolver
{
    protected $challengeService;

    public function __construct(ChallengeService $challengeService)
    {
        $this->challengeService = $challengeService;
    }

    public function sendChallengeInvite($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'agora_channel_id' => 'required',
            'teammate_user_id' => 'nullable',
            'round_duration' => 'nullable|integer',
            'coin_amount' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $this->challengeService->sendInvite($input, $authUser);

            return [
                'success' => true,
                'message' => 'Meydan okuma daveti başarıyla gönderildi',
            ];
        } catch (Exception $e) {
            Log::error('Failed to send challenge invite to user.', [
                'user_id' => $authUser->id ?? null,
                'agora_channel_id' => $input['agora_channel_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function respondToChallengeInvite($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'challenge_invite_id' => 'required',
            'response' => 'required|boolean',
        ], [
            'challenge_invite_id.required' => __('validation.required', ['attribute' => 'Davet']),
            'response.required' => __('validation.required', ['attribute' => 'Cevap']),
            'response.boolean' => __('validation.boolean', ['attribute' => 'Cevap']),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $this->challengeService->respondToInvite($input, $authUser);

            return [
                'success' => true,
                'message' => 'Meydan okuma daveti başarıyla cevaplandı.'
            ];
        } catch (Exception $e) {
            Log::error('Failed to respond to challenge invite.', [
                'user_id' => $authUser->id ?? null,
                'challenge_invite_id' => $input['challenge_invite_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
