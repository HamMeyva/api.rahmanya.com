<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PKBattle extends Model
{
    protected $table = 'pk_battles';

    protected $appends = [];

    protected $fillable = [
        'live_stream_id',
        'battle_id',
        'challenge_id',              // ✅ NEW: Link to Challenge record
        'challenger_id',
        'opponent_id',
        'opponent_stream_id',       // Opponent's stream ID for broadcasting
        'cohost_stream_ids',         // Additional cohost stream IDs (not in PK)
        'status',
        'battle_phase',
        'countdown_duration',
        'countdown_seconds',         // v2.0: Countdown between rounds
        'countdown_started_at',
        'server_sync_time',
        'last_activity_at',
        'challenger_score',
        'opponent_score',
        'challenger_gift_count',
        'opponent_gift_count',
        'total_gift_value',
        'challenger_stream_status',
        'opponent_stream_status',
        'battle_config',
        'error_logs',
        'winner_id',
        'started_at',
        'ended_at',
        // v2.0: Multi-round fields
        'total_rounds',
        'current_round',
        'round_duration_minutes',
        'round_started_at',
        'round_ends_at',
        'round_scores',
        'battle_settings',
        'is_round_active',
        'challenger_goals',          // Rounds won by challenger
        'opponent_goals',            // Rounds won by opponent
        'shoots_per_goal',           // v2.0: SHOOT → Goals threshold
        'goals_to_win',              // v2.0: Instant win condition
        'duration_seconds',          // v2.0: Battle duration in seconds
    ];

    protected $casts = [
        'challenger_score' => 'integer',
        'opponent_score' => 'integer',
        'challenger_gift_count' => 'integer',
        'opponent_gift_count' => 'integer',
        'total_gift_value' => 'integer',
        'countdown_duration' => 'integer',
        'countdown_seconds' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'countdown_started_at' => 'datetime',
        'server_sync_time' => 'datetime',
        'last_activity_at' => 'datetime',
        'round_started_at' => 'datetime',
        'round_ends_at' => 'datetime',
        'battle_config' => 'array',
        'battle_settings' => 'array',
        'error_logs' => 'array',
        'round_scores' => 'array',
        'cohost_stream_ids' => 'array',
        // v2.0 fields
        'total_rounds' => 'integer',
        'current_round' => 'integer',
        'round_duration_minutes' => 'integer',
        'is_round_active' => 'boolean',
        'challenger_goals' => 'integer',
        'opponent_goals' => 'integer',
        'shoots_per_goal' => 'integer',
        'goals_to_win' => 'integer',
        'duration_seconds' => 'integer',
    ];

    // public function liveStream(): BelongsTo
    // {
    //     return $this->belongsTo(LiveStream::class);
    // }

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * ✅ NEW: Get the Challenge record linked to this PK Battle
     */
    public function challenge()
    {
        return $this->belongsTo(\App\Models\Challenge\Challenge::class, 'challenge_id', '_id');
    }

    /**
     * Get all rounds for this battle (v2.0)
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(PKRound::class, 'pk_battle_id')->orderBy('round_number');
    }

    /**
     * Get the current active round (v2.0)
     */
    public function currentRoundModel(): HasOne
    {
        return $this->hasOne(PKRound::class, 'pk_battle_id')
            ->where('round_number', $this->current_round ?? 1);
    }

    /**
     * Get total goals for challenger across all rounds (v2.0)
     */
    public function getTotalGoalsAAttribute(): int
    {
        return $this->rounds()->sum('goals_a');
    }

    /**
     * Get total goals for opponent across all rounds (v2.0)
     */
    public function getTotalGoalsBAttribute(): int
    {
        return $this->rounds()->sum('goals_b');
    }

    /**
     * Get total score for challenger across all rounds (v2.0)
     */
    public function getTotalScoreAAttribute(): int
    {
        return $this->rounds()->sum('score_a');
    }

    /**
     * Get total score for opponent across all rounds (v2.0)
     */
    public function getTotalScoreBAttribute(): int
    {
        return $this->rounds()->sum('score_b');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function start(): void
    {
        $this->status = 'ACTIVE';
        $this->started_at = now();
        $this->save();
    }

    public function finish(?int $winnerId = null): void
    {
        $this->status = 'FINISHED';
        $this->ended_at = now();
        $this->winner_id = $winnerId;
        $this->save();
    }
}
