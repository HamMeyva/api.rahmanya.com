<?php

namespace App\GraphQL\Resolvers\Challenge;

use Exception;
use Illuminate\Support\Facades\Log;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Services\Challenges\ChallengeStarterService;
use App\Models\Challenge\Challenge;

class ChallengeResolver
{
    protected $challengeStarterService;

    public function __construct(ChallengeStarterService $challengeStarterService)
    {
        $this->challengeStarterService = $challengeStarterService;
    }
    public function startChallenge($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();

        try {
            $this->challengeStarterService->start($authUser);

            return [
                'success' => true,
                'message' => 'Meydan okuma başlatıldı.'
            ];
        } catch (Exception $e) {
            Log::error('Failed to start challenge.', [
                'user_id' => $authUser->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
