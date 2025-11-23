<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftTransaction extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'live_stream_id',
        'gift_id',
        'coin_amount',
        'pk_battle_id'
    ];

    protected $casts = [
        'coin_amount' => 'integer',
        'sent_at' => 'datetime'
    ];

    protected $dates = ['sent_at'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    public function pkBattle(): BelongsTo
    {
        return $this->belongsTo(PKBattle::class, 'pk_battle_id');
    }

    public function scopeForLiveStream($query, $liveStreamId)
    {
        return $query->where('live_stream_id', $liveStreamId);
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('sent_at', 'desc')->limit($limit);
    }
}
