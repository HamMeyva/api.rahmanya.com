<?php

namespace App\GraphQL\Queries;

use App\Services\LiveStream\ComprehensivePKBattleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PKBattleStats
{
    protected ComprehensivePKBattleService $pkBattleService;

    public function __construct(ComprehensivePKBattleService $pkBattleService)
    {
        $this->pkBattleService = $pkBattleService;
    }

    /**
     * Get PK battle statistics
     *
     * @param  null  $rootValue
     * @param  array<string, mixed>  $args
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $battleId = $args['battleId'];

        $stats = $this->pkBattleService->getBattleStats($battleId);

        return [
            'success' => true,
            'data' => $stats,
        ];
    }
}
