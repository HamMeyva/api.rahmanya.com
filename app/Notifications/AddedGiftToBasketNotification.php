<?php

namespace App\Notifications;

use App\Models\Gift;
use App\Helpers\Variable;
use App\Models\GiftBasket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddedGiftToBasketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_ADDED_GIFT_TO_BASKET;

    public function __construct(protected Gift $gift, protected GiftBasket $giftBasket)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$this->giftBasket->quantity} adet {$this->gift->name} çantaya eklendi.";
        $this->data = [
            'gift_id' => $this->gift->_id,
            'gift_basket_id' => $this->giftBasket->_id,
            'quantity' => $this->giftBasket->quantity,
            'gift_name' => $this->gift->name,
        ];
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        if ($notifiable->fcm_token && $notifiable->accept_push) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toFcm($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }
}
