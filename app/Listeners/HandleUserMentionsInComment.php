<?php

namespace App\Listeners;

use Exception;
use App\Models\User;
use App\Events\VideoCommented;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\UserMentionedInCommentNotification;

class HandleUserMentionsInComment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(VideoCommented $event): void
    {
        try {
            $comment = $event->comment;
            
            // Find all @mentions in the comment
            preg_match_all('/@([\w.]+)/', $comment->content, $matches);
            
            if (empty($matches[1])) {
                return; // No mentions found
            }
            
            $usernames = array_unique($matches[1]);
            
            // Find users by their usernames
            $users = User::whereIn('username', $usernames)->get();
            
            foreach ($users as $user) {
                // Skip if the mentioned user has disabled taggable notifications
                if (!$user->taggable_notification) {
                    continue;
                }
                
                // Send notification to the mentioned user
                $user->notify(new UserMentionedInCommentNotification($comment, $user));
                
                Log::info('User mentioned in comment notification sent', [
                    'recipient_id' => $user->id,
                    'commenter_id' => $comment->user_id,
                    'comment_id' => $comment->id,
                    'video_id' => $comment->video_id
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error handling user mentions in comment: ' . $e->getMessage(), [
                'exception' => $e,
                'comment_id' => $event->comment->id ?? 'unknown'
            ]);
        }
    }
}
