<?php

namespace App\Notifications\Admin;

use App\Helpers\Variable;
use App\Models\Morph\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;


class EftPaymentCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $url;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_EFT_PAYMENT_CREATED;

    public function __construct(protected Payment $payment)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$this->payment->user->nickname} adlı kullanıcı {$this->payment->draw_total_amount} tutarında EFT ödemesi gerçekleştirdi. Onay bekliyor.";
        $this->url = route('admin.payments.waiting-approval');
    }

    public function via(): array
    {
        return ['broadcast', 'mail', 'database'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => [
                'notification_type' => $this->notification_type,
                'title' => $this->title,
                'body' => $this->body,
                'url' => $this->url
            ]
        ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'data' => [
                'payment_id' => $this->payment->id,
                'total_amount' => $this->payment->total_amount,
                'user_id' => $this->payment->user_id,
                'user_nickname' => $this->payment->user->nickname,
            ]
        ];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->subject($this->title)
            ->view('mail.eft-payment-created', ['title' => $this->title, 'body' => $this->body]);
    }
}
