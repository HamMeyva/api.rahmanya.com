<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\Coin\CoinWithdrawalRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CoinWithdrawalRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_COIN_WITHDRAWAL_REQUEST_REJECTED;

    public function __construct(protected CoinWithdrawalRequest $coinWithdrawalRequest)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$coinWithdrawalRequest->coin_amount} adet Shoot Coin çekim talebi reddedildi: Neden: " . Str::limit($coinWithdrawalRequest->reject_reason, 50);
        $this->data = [
            'coin_withdrawal_request_id' => $coinWithdrawalRequest->id,
            'coin_amount' => $coinWithdrawalRequest->coin_amount,
            'reject_reason' => $coinWithdrawalRequest->reject_reason,
            'rejected_at' => $coinWithdrawalRequest->rejected_at,
        ];
    }

    public function via(object $notifiable): array
    {
        $channels = [
            'database',
            'broadcast',
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
            'notification_type' => $this->notification_type,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ]);
    }
}
