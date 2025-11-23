<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MixerLog extends Model
{
    protected $fillable = [
        'id',
        'mixer_session_id',
        'action',
        'request_payload',
        'response_payload',
        'status_code',
        'error_message'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array'
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
     * Check if the action was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300 && !$this->error_message;
    }
}