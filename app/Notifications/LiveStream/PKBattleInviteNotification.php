<?php

namespace App\Notifications\LiveStream;

use App\Helpers\Variable;
use App\Models\PKBattle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PKBattleInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $notification_type = 'pk_battle_invite';
    protected array $data;

    public function __construct(protected PKBattle $pkBattle)
    {
        $challengerName = $this->pkBattle->challenger->nickname ?? $this->pkBattle->challenger->name ?? 'Bilinmeyen';
        $this->title = 'PK Savaşı Daveti';
        $this->body = $challengerName;
        $this->data = [
            'pk_battle_id' => $this->pkBattle->id,
            'live_stream_id' => $this->pkBattle->live_stream_id,
            'challenger_id' => $this->pkBattle->challenger_id,
            'challenger_name' => $challengerName,
            'challenger_avatar' => $this->pkBattle->challenger->avatar ?? null,
            'status' => $this->pkBattle->status,
            'total_rounds' => $this->pkBattle->total_rounds ?? 1,
            'round_duration_minutes' => $this->pkBattle->round_duration_minutes ?? 5,
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