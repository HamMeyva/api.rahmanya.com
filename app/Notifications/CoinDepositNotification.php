<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Relations\UserCoinTransaction;


class CoinDepositNotification extends Notification implements ShouldQueue
{
    use Queueable;


    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_COIN_DEPOSIT;


    public function __construct(protected UserCoinTransaction $userCoinTransaction)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$this?->userCoinTransaction?->amount} adet Shoot Coin alımı başarıyla tamamlandı.";
        $this->data = [
            'type' => $this?->userCoinTransaction?->transaction_type,
            'amount' => $this?->userCoinTransaction?->amount,
        ];
    }

    public function via(object $notifiable): array
    {
        $channels = [
            'database',
        ];

        if ($notifiable->fcm_token && $notifiable->general_push_notification) {
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
            'title' => $this->title,
            'body' => $this->body,
            'data' => [
                'type' => $this->userCoinTransaction->transaction_type,
                'amount' => $this->userCoinTransaction->amount,
            ],
        ];
    }
}
