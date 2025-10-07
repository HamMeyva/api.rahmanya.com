<?php

namespace App\Listeners;

use Exception;
use App\Models\User;
use App\Events\VideoLiked;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\VideoLikedNotification;

class SendVideoLikeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct() {}

    public function handle(VideoLiked $event): void
    {
        try {
            $like = $event->videoLike;
            
            $user = $like->user();
            $video = $like->video;
            $videoOwner = $video->user();
        
            if (!$video || !$videoOwner) {
                Log::error('Video or owner not found for like', ['like_id' => $like->id]);
                return;
            }
            
            if ($user->id === $videoOwner->id) {
                return;
            }
            
            $videoOwner->notify(new VideoLikedNotification($like));
            
            Log::info('Video like notification sent', [
                'recipient_id' => $videoOwner->id,
                'liker_id' => $user->id,
                'video_id' => $video->id
            ]);
        } catch (Exception $e) {
            Log::error('Error sending video like notification: ' . $e->getMessage(), [
                'exception' => $e,
                'like_id' => $event->videoLike->id ?? 'unknown'
            ]);
            
            throw $e;
        }
    }
}
