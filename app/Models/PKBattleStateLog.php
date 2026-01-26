<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PKBattleStateLog extends Model
{
    use HasFactory;

    protected $table = 'pk_battle_state_logs';

    protected $fillable = [
        'pk_battle_id',
        'event_type',
        'event_data',
        'user_id',
        'server_timestamp',
        'client_timestamp',
    ];

    protected $casts = [
        'event_data' => 'array',
        'server_timestamp' => 'datetime',
        'client_timestamp' => 'datetime',
    ];

    public function pkBattle(): BelongsTo
    {
        return $this->belongsTo(PKBattle::class, 'pk_battle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Create a new state log entry
     */
    public static function logEvent(
        int $pkBattleId,
        string $eventType,
        array $eventData = null,
        string $userId = null,
        \DateTime $clientTimestamp = null
    ): self {
        return self::create([
            'pk_battle_id' => $pkBattleId,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'user_id' => $userId,
            'server_timestamp' => now(),
            'client_timestamp' => $clientTimestamp,
        ]);
    }
}