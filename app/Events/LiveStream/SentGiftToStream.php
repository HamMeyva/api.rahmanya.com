<?php

namespace App\Events\LiveStream;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class SentGiftToStream
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        protected $sender,
        protected $giftBasket,
        protected $channel
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("live-stream.{$this->channel->id}.gifts");
    }

    public function broadcastWith(): array
    {
        return [
            'body' => "{$this?->sender?->nickname} {$this?->giftBasket?->quantity} adet {$this?->giftBasket?->gift?->name} gönderdi.",
            'gift_id' => $this?->giftBasket?->gift_id ?? null,
            'gift_name' => $this?->giftBasket?->gift?->name ?? null,
            'sender' => [
                'id' => $this?->sender?->id ?? null,
                'nickname' => $this?->sender?->nickname ?? null,
                'avatar' => $this->sender->avatar,
            ],
        ];
    }
}
