<?php

namespace App\Models\Agora;

use App\Models\Gift;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use Mongodb\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\Agora\AgoraChannelMessageObserver;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperAgoraChannelMessage
 */
#[ObservedBy(AgoraChannelMessageObserver::class)]
class AgoraChannelMessage extends Model
{

    protected $connection = 'mongodb';

    protected $collection = 'agora_channel_messages';

    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',
        'user_id',
        'user_data',
        'admin_id',
        'admin_data',
        'message',
        'original_message',
        'has_banned_word',
        'is_pinned',
        'is_blocked',
        'timestamp',
        'parent_message_id',
        'gift_id',
        'gift_data',
    ];

    protected $casts = [
        'created_at' => DatetimeTz::class,
        'updated_at' => DatetimeTz::class,
        'is_pinned' => 'boolean',
        'is_blocked' => 'boolean',
        'gift_id' => 'integer',
        'has_banned_word' => 'boolean',
        'gift_amount' => 'integer',
        'sticker_id' => 'integer'
    ];

    public function agoraChannel()
    {
        return AgoraChannel::find($this->agora_channel_id);
    }

    public function user()
    {
        return User::find($this->user_id);
    }


    public function gift()
    {
        return $this->gift_id ? Gift::find($this->gift_id) : null;
    }


    public function parentMessage()
    {
        return $this->parent_message_id ? self::find($this->parent_message_id) : null;
    }

    public function replies()
    {
        return self::where('parent_message_id', $this->_id)->get();
    }

    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('agora_channel_id', $channelId);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeWithGift($query)
    {
        return $query->whereNotNull('gift_id');
    }
}
