<?php

namespace App\Notifications;

use App\Models\Video;
use App\Helpers\Variable;
use Illuminate\Support\Str;
use App\Models\VideoComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VideoCommentedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;

    protected string $notification_type = Variable::NOTIFICATION_TYPE_VIDEO_COMMENTED;

    protected Video $video;

    public function __construct(protected VideoComment $comment)
    {
        $commenter = $this->comment->user();
        $this->video = $this->comment->video;

        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$commenter->nickname} videona yorum yaptı: " . Str::limit($this->comment->content, 50);
        $this->data = [
            'sender_id' => $commenter->id,
            'sender_name' => $commenter->nickname,
            'sender_avatar' => $commenter->avatar,
            'target_id' => (string) $this->video->id,
            'target_type' => 'video',
            'action' => 'comment',
            'comment_id' => (string) $this->comment->id,
            'comment_content' => $this->comment->content,
            'video_id' => (string) $this->video->id,
            'video_thumbnail' => $this->video?->thumbnail_url ?? null,
        ];
    }

    public function via($notifiable): array
    {
        if ($this->video->user_id != $notifiable->id) return [];

        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token && $notifiable->comment_notification) {
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
