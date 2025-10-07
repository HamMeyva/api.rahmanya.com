<?php

namespace App\Notifications;

use App\Helpers\Variable;
use App\Models\Morph\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class EftPaymentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_EFT_PAYMENT_REJECTED;
    protected array $data;

    public function __construct(protected Payment $payment)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];

        $failureReason = $this->payment->failure_reason ? "{$this->payment->failure_reason}" : 'Belirtilmemiş';
        $this->body = "`{$this->payment->draw_total_amount}` tutarında havale ödemeniz `{$failureReason}`nedeniyle reddedildi.";
        $this->data = [
            'payment_id' => $this->payment->id,
            'total_amount' => $this->payment->total_amount,
            'failure_reason' => $failureReason,
        ];
    }

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        if ($notifiable->email_verified_at) {
            $channels[] = 'mail';
        }

        if ($notifiable->phone_verified_at) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toDatabase(): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
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

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->subject($this->title)
            ->view('mail.eft-payment-rejected', ['title' => $this->title, 'body' => $this->body]);
    }

    public function toSms(): string
    {
        return $this->body;
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
