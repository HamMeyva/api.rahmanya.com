<?php

namespace App\Notifications\LiveStream;

use App\Helpers\Variable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AgoraChannelInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_AGORA_CHANNEL_INVITE;
    protected array $data;

    public function __construct(protected $agoraChannelInvite)
    {
        $channelName = $this->agoraChannelInvite->agora_channel->name ?? '';
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = $this->agoraChannelInvite->user_data['nickname'] ?? 'Bilinmeyen' . " tarafından {$channelName} başlıklı canlı yayına davet edildiniz.";
        $this->data = [
            'agora_channel_id' => $this->agoraChannelInvite->agora_channel_id,
            'agora_channel_invite_id' => $this->agoraChannelInvite->_id,
        ];
    }


    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

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
