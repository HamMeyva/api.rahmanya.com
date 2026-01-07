<?php

namespace App\GraphQL\Mutations;

use App\Services\LiveStream\ComprehensivePKBattleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class EndPKBattle
{
    protected ComprehensivePKBattleService $pkBattleService;

    public function __construct(ComprehensivePKBattleService $pkBattleService)
    {
        $this->pkBattleService = $pkBattleService;
    }

    /**
     * End a PK battle
     *
     * @param  null  $rootValue
     * @param  array<string, mixed>  $args
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $battleId = $args['battleId'];

        $result = $this->pkBattleService->endPKBattle($battleId, false);

        return $result;
    }
}
