<?php

namespace App\Jobs\LiveStreamGift;

use App\Models\Agora\AgoraChannelGift;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Queue\Queueable;

class StoreGiftInAgoraChannel
{
    use Queueable;

    public function __construct(
        public $userId,
        public $recipientUserId,
        public $giftId,
        public $agoraChannel,
        public $totalCost,
        public $quantity
    ) {}

    public function handle(): void
    {
        // Arka arkaya gönderilen hediyelerde streak olayı
        $streakKey = "gift_streak:{$this->agoraChannel->id}:{$this->userId}:{$this->recipientUserId}:{$this->giftId}";

        $currentStreak = Redis::get($streakKey);
    
        $streak = $currentStreak ? (int) $currentStreak + $this->quantity : $this->quantity;

        Redis::set($streakKey, $streak, 'EX', 60);
        //*
        

        AgoraChannelGift::create([
            'agora_channel_id' => $this->agoraChannel->id,
            'challenge_id' => $this->agoraChannel->is_challenge_active ? $this->agoraChannel->activeChallenge?->id : null, // eğer pk'da ise challenge idsini e belirtiyoruz
            'gift_id' => $this->giftId,
            'user_id' => $this->userId,
            'recipient_user_id' => $this->recipientUserId,
            'coin_value' => $this->totalCost,
            'quantity' => $this->quantity,
            'streak' => $streak,
        ]);
    }
}
