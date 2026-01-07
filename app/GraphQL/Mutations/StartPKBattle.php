<?php

namespace App\GraphQL\Mutations;

use App\Services\LiveStream\ComprehensivePKBattleService;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Auth;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class StartPKBattle
{
    protected ComprehensivePKBattleService $pkBattleService;

    public function __construct(ComprehensivePKBattleService $pkBattleService)
    {
        $this->pkBattleService = $pkBattleService;
    }

    /**
     * Start a new PK battle
     *
     * @param  null  $rootValue
     * @param  array<string, mixed>  $args
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];
        $user = $context->user();

        $result = $this->pkBattleService->startPKBattle(
            $input['liveStreamId'],
            $input['opponentId'],
            $input['opponentStreamId'] ?? null,
            $input['durationSeconds'] ?? 300
        );

        return $result;
    }
}
