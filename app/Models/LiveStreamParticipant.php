<?php

namespace App\Models;

use App\Models\Agora\AgoraChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveStreamParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'user_id',
        'role',
        'participant_type',
        'zego_stream_id',
        'audio_enabled',
        'video_enabled',
        'joined_at',
        'left_at',
        'is_active',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
        'audio_enabled' => 'boolean',
        'video_enabled' => 'boolean',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(AgoraChannel::class, 'live_stream_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get isCoHost attribute for GraphQL
     */
    public function getIsCoHostAttribute()
    {
        return $this->participant_type === 'co_host' || $this->role === 'cohost';
    }
}
