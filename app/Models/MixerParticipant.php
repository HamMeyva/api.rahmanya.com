<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MixerParticipant extends Model
{
    protected $fillable = [
        'id',
        'mixer_session_id',
        'stream_id',
        'user_id',
        'chat_room_id',
        'position',
        'joined_at',
        'left_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime'
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * Get the mixer session
     */
    public function mixerSession(): BelongsTo
    {
        return $this->belongsTo(MixerSession::class, 'mixer_session_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if participant is active
     */
    public function isActive(): bool
    {
        return is_null($this->left_at);
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->joined_at) {
            return null;
        }

        $endTime = $this->left_at ?? now();
        return $this->joined_at->diffInSeconds($endTime);
    }
}