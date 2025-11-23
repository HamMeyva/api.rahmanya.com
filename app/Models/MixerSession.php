<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MixerSession extends Model
{
    protected $fillable = [
        'id',
        'task_id',
        'mixed_stream_id',
        'mixed_stream_url',
        'layout_type',
        'status',
        'config'
    ];

    protected $casts = [
        'config' => 'array'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get participants for this mixer session
     */
    public function participants(): HasMany
    {
        return $this->hasMany(MixerParticipant::class, 'mixer_session_id');
    }

    /**
     * Get active participants
     */
    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    /**
     * Get logs for this session
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MixerLog::class, 'mixer_session_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get live streams associated with this mixer
     */
    public function liveStreams(): HasMany
    {
        return $this->hasMany(LiveStream::class, 'mixer_session_id');
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get participant count
     */
    public function getParticipantCountAttribute(): int
    {
        return $this->activeParticipants()->count();
    }
}