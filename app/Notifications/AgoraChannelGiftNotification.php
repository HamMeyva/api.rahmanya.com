<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AgoraChannelGiftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_AGORA_CHANNEL_GIFT;

    public function __construct(protected $sender, protected $giftBasket, protected $channel)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$this?->sender?->nickname} {$this?->giftBasket?->quantity} adet {$this?->giftBasket?->gift?->name} gönderdi.";
        $this->data = [
            'gift_id' => $this?->giftBasket?->gift_id ?? null,
            'gift_name' => $this?->giftBasket?->gift?->name ?? null,
            'sender' => [
                'id' => $this?->sender?->id ?? null,
                'nickname' => $this?->sender?->nickname ?? null,
                'avatar' => $this?->sender?->avatar ?? null,
            ],
        ];
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data
        ];
    }
}
