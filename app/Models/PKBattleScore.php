<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PKBattleScore extends Model
{
    protected $table = 'pk_battle_scores';

    protected $fillable = [
        'pk_battle_id',
        'user_id',
        'streamer_id',
        'gift_id',
        'gift_value',
        'quantity',
        'total_value',
        'gift_transaction_id',
    ];

    protected $casts = [
        'gift_value' => 'integer',
        'quantity' => 'integer',
        'total_value' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pkBattle(): BelongsTo
    {
        return $this->belongsTo(PKBattle::class, 'pk_battle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function streamer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'streamer_id');
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }

    public function giftTransaction(): BelongsTo
    {
        return $this->belongsTo(GiftTransaction::class, 'gift_transaction_id');
    }

    /**
     * Get total score for a streamer in a battle
     */
    public static function getStreamerScore(int $battleId, string $streamerId): int
    {
        return static::where('pk_battle_id', $battleId)
            ->where('streamer_id', $streamerId)
            ->sum('total_value');
    }

    /**
     * Get top gift senders for a streamer in a battle
     */
    public static function getTopSenders(int $battleId, string $streamerId, int $limit = 10): array
    {
        $scores = static::where('pk_battle_id', $battleId)
            ->where('streamer_id', $streamerId)
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($gifts) {
                $user = $gifts->first()->user;
                return [
                    'user_id' => $user->id,
                    'user' => $user,
                    'total_value' => $gifts->sum('total_value'),
                    'gift_count' => $gifts->sum('quantity'),
                ];
            })
            ->sortByDesc('total_value')
            ->take($limit)
            ->values()
            ->toArray();

        return $scores;
    }

    /**
     * Get battle statistics
     */
    public static function getBattleStats(int $battleId): array
    {
        $scores = static::where('pk_battle_id', $battleId)->get();

        return [
            'total_gifts' => $scores->sum('quantity'),
            'total_value' => $scores->sum('total_value'),
            'unique_senders' => $scores->unique('user_id')->count(),
        ];
    }
}
