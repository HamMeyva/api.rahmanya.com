<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use App\Models\Coin\CoinWithdrawalRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CoinWithdrawalRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;
    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_COIN_WITHDRAWAL_REQUEST_APPROVED;
    protected array $data;

    public function __construct(protected CoinWithdrawalRequest $coinWithdrawalRequest)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "Çekim talebi başarıyla onaylandı.";
        $this->data = [
            'coin_withdrawal_request_id' => $this->coinWithdrawalRequest->_id,
            'coin_amount' => $this->coinWithdrawalRequest->coin_amount,
            'coin_unit_price' => $this->coinWithdrawalRequest->coin_unit_price,
            'coin_total_price' => $this->coinWithdrawalRequest->coin_total_price,
            'status_id' => $this->coinWithdrawalRequest->status_id,
            'approved_at' => $this->coinWithdrawalRequest->approved_at,
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
    
 
    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->body,
            'type' => 'coin_withdrawal_request_approved',
            'data' => $this->data,
        ];
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
