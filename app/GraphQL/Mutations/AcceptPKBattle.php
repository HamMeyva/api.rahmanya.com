<?php

namespace App\GraphQL\Mutations;

use App\Services\LiveStream\EnhancedPKBattleService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Log;

class AcceptPKBattle
{
    public $withinTransaction = false; // Disable automatic transaction wrapping
    
    protected $pkBattleService;
    
    public function __construct(EnhancedPKBattleService $pkBattleService)
    {
        $this->pkBattleService = $pkBattleService;
    }
    
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        $battleId = $args['battleId'];
        
        try {
            Log::info('PK Battle acceptance request', [
                'battle_id' => $battleId,
                'user_id' => $user->id,
            ]);
            
            $battle = $this->pkBattleService->acceptPKBattle($battleId, $user->id);
            
            return [
                'success' => true,
                'message' => 'PK savaşı kabul edildi ve başladı!',
                'battle' => $battle,
            ];
            
        } catch (\Exception $e) {
            Log::error('PK Battle acceptance failed', [
                'error' => $e->getMessage(),
                'battle_id' => $battleId,
                'user_id' => $user->id,
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'battle' => null,
            ];
        }
    }
}