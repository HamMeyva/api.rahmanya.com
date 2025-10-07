<?php

namespace App\GraphQL\Resolvers;

use App\Models\Punishment;
use App\Models\UserPunishment;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PunishmentResolver
{
    public function getPunishments($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $perPage = $args['per_page'] ?? 10;
        $page = $args['page'] ?? 1;

        $punishments = Punishment::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => $punishments->items(),
            'total' => $punishments->total(),
            'page' => $punishments->currentPage(),
            'per_page' => $punishments->perPage()
        ];
    }

    public function myPunishments($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $authUser = Auth::user();

        $perPage = $args['per_page'] ?? 10;
        $page = $args['page'] ?? 1;

        $userPunishments = UserPunishment::query()
            ->where('user_id', $authUser->id)
            ->orderBy('applied_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data' => $userPunishments->items(),
            'total' => $userPunishments->total(),
            'page' => $userPunishments->currentPage(),
            'per_page' => $userPunishments->perPage()
        ];
    }
}
