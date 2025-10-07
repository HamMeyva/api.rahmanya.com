<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Support\Str;
use App\Models\VideoComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CommentRepliedNotification extends Notification implements ShouldQueue
{

    use Queueable;

    protected string $notifiableUserId = '';
    protected string $title;
    protected string $body;
    protected array $data;

    protected string $notification_type = Variable::NOTIFICATION_TYPE_COMMENT_REPLYED;

    public function __construct(protected VideoComment $replyComment, protected VideoComment $parentComment)
    {
        $commenter = $this->replyComment->user();
        $video = $this->replyComment->video;


        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = $commenter->nickname . ' yorumuna yanıt verdi: ' . Str::limit($this->replyComment->content, 50);
        $this->data = [
            'sender_id' => $commenter->id,
            'sender_name' => $commenter->nickname,
            'sender_avatar' => $commenter->avatar,
            'target_id' => (string) $video->id,
            'target_type' => 'video',
            'action' => 'comment_reply',
            'comment_id' => (string) $this->replyComment->id,
            'parent_comment_id' => (string) $this->parentComment->id,
            'comment_content' => $this->replyComment->content,
            'video_id' => (string) $video->id,
            'video_thumbnail' => $video->thumbnail_url ?? null,
        ];

        $this->notifiableUserId = $this->replyComment->user_id;
    }

    public function via($notifiable): array
    {
        if ($this->replyComment->user_id === $notifiable->id) return [];

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
