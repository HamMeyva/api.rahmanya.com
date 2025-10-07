<?php

namespace App\Listeners;

use Exception;
use App\Models\VideoComment;
use App\Events\VideoCommented;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\VideoCommentedNotification;

class SendVideoCommentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // No dependencies needed as we'll use Laravel's notification system
    }

    /**
     * Handle the event.
     */
    public function handle(VideoCommented $event): void
    {
        try {
            $comment = $event->comment;
            
            // Determine if this is a reply to another comment or a direct video comment
            $isReply = !empty($comment->parent_id) || isset($comment->parent_id);
            
            if ($isReply) {
                $this->handleCommentReply($comment);
            } else {
                $this->handleVideoComment($comment);
            }
        } catch (Exception $e) {
            Log::error('Error sending video comment notification: ' . $e->getMessage(), [
                'exception' => $e,
                'comment_id' => $event->comment->id ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Handle notification for a reply to another comment
     */
    private function handleCommentReply(VideoComment $comment): void
    {
        // Load relationships if not already loaded
        $comment->loadMissing('video');
        
        // Get the parent comment and its owner
        $parentComment = $comment->parent;
        if (!$parentComment) {
            Log::error('Parent comment not found for comment: ' . $comment->id);
            return;
        }
        
        $parentCommentOwner = $parentComment->user();
        if (!$parentCommentOwner) {
            Log::error('Parent comment owner not found for comment: ' . $parentComment->id);
            return;
        }
        
        // Skip if the user replied to their own comment
        if ($comment->user_id === $parentCommentOwner->id) {
            return;
        }
        
        // Skip if the parent comment owner has disabled comment notifications
        if (!$parentCommentOwner->comment_notification) {
            return;
        }
        
        // Send the notification
        $parentCommentOwner->notify(new CommentRepliedNotification($comment, $parentComment));
        
        Log::info('Comment reply notification sent', [
            'recipient_id' => $parentCommentOwner->id,
            'commenter_id' => $comment->user_id,
            'comment_id' => $comment->id,
            'parent_comment_id' => $parentComment->id
        ]);
    }
    
    /**
     * Handle notification for a direct comment on a video
     */
    private function handleVideoComment(VideoComment $comment): void
    {
        // Load relationships if not already loaded
        $comment->loadMissing('video');
        
        // Get the video and its owner
        $video = $comment->video;
        if (!$video) {
            Log::error('Video not found for comment: ' . $comment->id);
            return;
        }
        
        $videoOwner = $video->user();
        if (!$videoOwner) {
            Log::error('Video owner not found for video: ' . $video->id);
            return;
        }
        
        // Skip if the user commented on their own video
        if ($comment->user_id === $videoOwner->id) {
            return;
        }
        
        // Skip if the video owner has disabled comment notifications
        if (!$videoOwner->comment_notification) {
            return;
        }
        
        // Send the notification
        $videoOwner->notify(new VideoCommentedNotification($comment));
        
        Log::info('Video comment notification sent', [
            'recipient_id' => $videoOwner->id,
            'commenter_id' => $comment->user_id,
            'video_id' => $video->id,
            'comment_id' => $comment->id
        ]);
    }
}
