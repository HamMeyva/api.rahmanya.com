<?php

namespace App\Notifications;

use App\Helpers\Variable;
use Illuminate\Support\Str;
use App\Models\VideoComment;
use Illuminate\Bus\Queueable;
use App\Models\VideoCommentReaction;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CommentLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected array $data;
    protected string $notification_type = Variable::NOTIFICATION_TYPE_COMMENT_LIKED;
    protected VideoComment $comment;

    public function __construct(protected VideoCommentReaction $videoCommentReaction)
    {
        $liker = $videoCommentReaction->user() ?? null;
        $likerNickName = $liker->nickname ?? 'Bilinmeyen';
        $this->comment = $videoCommentReaction->comment;
        $video = $this->comment->video;


        $this->title = Variable::$notificationTypes[$this->notification_type];
        $this->body = "{$likerNickName} yorumunu beğendi: " . Str::limit($this->comment->content, 50);
        $this->data = [
            'sender_id' => $liker->id ?? null,
            'sender_name' => $liker->nickname ?? null,
            'sender_avatar' => $liker->avatar ?? null,
            'target_id' => (string) $video?->id ?? null,
            'target_type' => 'video',
            'action' => 'comment_like',
            'comment_id' => (string) $this->comment->id ?? null,
            'comment_content' => $this->comment->content ?? null,
            'video_id' => (string) $video->id ?? null,
            'video_thumbnail' => $video->thumbnail_url ?? null,
            'video_comment_reaction_id' => (string) $videoCommentReaction->id ?? null,
        ];
    }

    public function via($notifiable): array
    {
        if ($this->comment->user_id != $notifiable->id) return [];

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
