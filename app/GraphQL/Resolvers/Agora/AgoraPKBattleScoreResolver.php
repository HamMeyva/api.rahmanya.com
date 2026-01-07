<?php

namespace App\GraphQL\Resolvers\Agora;

use App\Models\PKBattle;
use App\Models\User;
use App\Services\LiveStream\PKBattleScoreService;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Auth;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraPKBattleScoreResolver
{
    protected $scoreService;

    public function __construct(PKBattleScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * PK Battle skorlarını getir
     */
    public function getPKBattleScores($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $battleId = $args['input']['battle_id'];

        $battle = PKBattle::findOrFail($battleId);
        
        // Kullanıcı bu battle'a katılıyor mu kontrol et
        if ($battle->challenger_id !== $user->id && $battle->opponent_id !== $user->id) {
            // İzleyici olarak da görebilir, o yüzden strict kontrol yapmıyoruz
        }

        return $this->scoreService->calculateAndUpdateScores($battle);
    }

    /**
     * PK Battle hediye istatistiklerini getir
     */
    public function getPKBattleGiftStats($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $battleId = $args['battle_id'];

        return $this->scoreService->getPKBattleGiftStats($battleId);
    }

    /**
     * PK Battle devresini sonlandır
     */
    public function endPKBattleRound($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $battleId = $args['input']['battle_id'];
        $forceEnd = $args['input']['force_end'] ?? false;

        $battle = PKBattle::findOrFail($battleId);
        
        // Sadece katılımcılar devre sonlandırabilir
        if ($battle->challenger_id !== $user->id && $battle->opponent_id !== $user->id) {
            throw new \Exception('Only battle participants can end rounds');
        }

        if ($battle->status !== 'ACTIVE') {
            throw new \Exception('Battle is not active');
        }

        return $this->scoreService->endPKBattleRound($battle, $forceEnd);
    }
}
