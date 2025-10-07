<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Video;
use App\Helpers\Variable;
use Illuminate\Support\Str;
use App\Models\VideoComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class UserMentionedInCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $notifiableUserId = '';
    protected string $title;
    protected string $body;
    protected array $data;

    protected string $notification_type = Variable::NOTIFICATION_TYPE_USER_MENTIONED_IN_COMMENT;

    protected Video $video;

    public function __construct(protected VideoComment $comment, protected User $mentionedUser)
    {
        $commenter = $this->comment->user;
        $video = $this->comment->video;

        $this->title = Variable::NOTIFICATION_TYPE_USER_MENTIONED_IN_COMMENT;
        $this->body =  "{$commenter->nickname} bir yorumda senden bahsetti: " . Str::limit($this->comment->content, 50);
        $this->data = [
            'sender_id' => $commenter->id,
            'sender_name' => $commenter->nickname,
            'sender_avatar' => $commenter->avatar,
            'target_id' => (string) $video->id,
            'target_type' => 'video',
            'action' => 'mention',
            'comment_id' => (string) $this->comment->id,
            'comment_content' => $this->comment->content,
            'video_id' => (string) $video->id,
            'video_thumbnail' => $video->thumbnail_url ?? null,
            'mentioned_user_id' => (string) $this->mentionedUser->id,
        ];

        $this->notifiableUserId = $this->mentionedUser->id;
    }

    public function via($notifiable): array
    {
        if ($this->mentionedUser->id != $notifiable->id) return [];

        $channels = ['database', 'broadcast'];

        if ($notifiable->fcm_token && $notifiable->taggable_notification) {
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
