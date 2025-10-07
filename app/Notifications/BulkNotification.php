<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class BulkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $notification_type = Variable::NOTIFICATION_TYPE_BULK;

    public function __construct(protected string $via, protected ?string $title, protected string $body) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($this->via === 'mail' && $notifiable->email_verified_at  && $notifiable->general_email_notification) {
            $channels[] = 'mail';
        } else if ($this->via === 'sms' && $notifiable->phone_verified_at && $notifiable->general_sms_notification) {
            $channels[] = 'sms';
        } else if ($this->via === 'push' && $notifiable->fcm_token && $notifiable->general_push_notification) {
            $channels[] = 'fcm';
            $channels[] = 'broadcast';
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toFcm($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' =>  $this->title,
            'body' => $this->body,
            'data' => [],
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'notification_type' => $this->notification_type,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification_type' => $this->notification_type,
            'title' => $this->title,
            'body' => $this->body,
            'data' => [],
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->title)
            ->view('mail.bulk', ['title' => $this->title, 'body' => $this->body]);
    }

    public function toSms(object $notifiable): string
    {
        return $this->body;
    }
}
