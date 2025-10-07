<?php

namespace App\Models\Agora;

use App\Models\Gift;
use App\Models\User;
use App\Helpers\CommonHelper;
use App\Models\Agora\AgoraChannel;
use Mongodb\Laravel\Eloquent\Model;
use App\Observers\Agora\AgoraChannelGiftObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;

#[ObservedBy(AgoraChannelGiftObserver::class)]
/**
 * @mixin IdeHelperAgoraChannelGift
 */
class AgoraChannelGift extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'agora_channel_gift';
    protected $fillable = [
        'agora_channel_id',
        'agora_channel_data',
        'challenge_id',
        'gift_id',
        'gift_data',
        'user_id',
        'user_data',
        'recipient_user_id',
        'recipient_user_data',
        'coin_value',
        'quantity',
        'message',
        'streak',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'coin_value' => 'integer',
            'quantity' => 'integer',
            'streak' => 'integer',
            'is_featured' => 'boolean'
        ];
    }

    protected $appends = [
        'total_value'
    ];

    public function agora_channel()
    {
        if (empty($this->agora_channel_id)) {
            return null;
        }
        
        if (!empty($this->agora_channel_data)) {
            return $this->getEmbeddedAgoraChannel();
        }
        
        return AgoraChannel::find($this->agora_channel_id);
    }
    

    protected function getEmbeddedAgoraChannel()
    {
        if (empty($this->agora_channel_data)) {
            return null;
        }
        
        $channel = new AgoraChannel();
        foreach ($this->agora_channel_data as $key => $value) {
            $channel->$key = $value;
        }
        
        return $channel;
    }


    public function user()
    {
        if (empty($this->user_id)) {
            return null;
        }
        
        if (!empty($this->user_data)) {
            return $this->getEmbeddedUser();
        }
        
        return User::find($this->user_id);
    }

    protected function getEmbeddedUser()
    {
        if (empty($this->user_data)) {
            return null;
        }
        
        $user = new User();
        foreach ($this->user_data as $key => $value) {
            $user->$key = $value;
        }
        
        return $user;
    }

    public function gift()
    {
        if (empty($this->gift_id)) {
            return null;
        }
        
        if (!empty($this->gift_data)) {
            return $this->getEmbeddedGift();
        }
        
        return Gift::find($this->gift_id);
    }

    protected function getEmbeddedGift()
    {
        if (empty($this->gift_data)) {
            return null;
        }
        
        $gift = new Gift();
        foreach ($this->gift_data as $key => $value) {
            $gift->$key = $value;
        }
        
        return $gift;
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }

    public function getTotalValueAttribute(): int
    {
        return ($this->coin_value ?? 0) * ($this->quantity ?? 1);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('agora_channel_id', $channelId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}