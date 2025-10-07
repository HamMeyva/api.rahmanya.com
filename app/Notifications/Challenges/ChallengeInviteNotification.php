<?php

namespace App\Notifications\Challenges;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Challenge\ChallengeInvite;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;
use App\Helpers\Variable;

class ChallengeInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_CHALLENGE_INVITE;
    protected array $data;

    public function __construct(protected ChallengeInvite $invite, protected string $channelUserId)
    {
        $this->title = Variable::$notificationTypes[$this->notification_type];
        $senderNickname = $this->invite->sender_user_data['nickname'] ?? 'Bilinmeyen';

        $teammateNickname = null;
        if (!empty($this->invite->teammate_user_data['nickname'])) {
            $teammateNickname = $this->invite->teammate_user_data['nickname'];
        }

        $body = "{$senderNickname} tarafından meydan okumaya davet edildiniz. Round Süresi: {$this->invite->round_duration} saniye, Coin Miktarı: {$this->invite->coin_amount}.";

        if ($teammateNickname) {
            $body .= " {$senderNickname} takım arkadaşı olarak {$teammateNickname} adlı yayıncıyı seçti.";
        }

        $this->body = $body;
        $this->data = [
            'challenge_invite_id' => $this->invite->_id,
        ];
    }

    public function via($notifiable): array
    {
        $channels = ['broadcast', 'database'];

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
