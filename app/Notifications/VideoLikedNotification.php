<?php

namespace App\Notifications;

use App\Helpers\Variable;
use App\Models\VideoLike;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VideoLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;

    protected string $notification_type = Variable::NOTIFICATION_TYPE_VIDEO_LIKED;

    public function __construct(protected VideoLike $like)
    {
        $liker = $like->user() ?? null;
        $likerNickName = $liker->nickname ?? 'Bilinmeyen';
        $video = $like->video;

        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$likerNickName} videonu beğendi.";
        $this->data = [
            'sender_id' => $liker->id,
            'sender_name' => $liker->nickname,
            'sender_avatar' => $liker->avatar,
            'target_id' => (string) $video->id,
            'target_type' => 'video',
            'action' => 'like',
            'video_id' => (string) $video->id,
            'video_thumbnail' => $video->thumbnail_url ?? null,
        ];
    }

    public function via($notifiable): array
    {
        if ($this->like->user_id === $notifiable->id) return [];

        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token && $notifiable->like_notification) {
            $channels[] = 'fcm';
        }

        return $channels;
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

    public function toFcm($notifiable): array
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
