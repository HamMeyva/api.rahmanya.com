<?php

namespace App\GraphQL\Resolvers;

use App\Models\Relations\Team;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TeamResolver
{
    /**
     * Fetch all teams
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function fetchAllTeams($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $teams = Team::all();

        return [
            'success' => true,
            'teams' => $teams,
        ];
    }
}
