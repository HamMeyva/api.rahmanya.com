<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoComment;
use App\Models\VideoLike;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcileVideoCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Video sayaçları reconciliation işlemi başlatıldı');
        $updatedCount = 0;
        
        // Tüm videoları 100'er 100'er işle
        Video::chunk(100, function ($videos) use (&$updatedCount) {
            foreach ($videos as $video) {
                // Gerçek beğeni ve yorum sayılarını hesapla
                $likesCount = VideoLike::where('video_id', $video->_id)->count();
                $commentsCount = VideoComment::where('video_id', $video->_id)->count();
                
                // Sadece değişiklik varsa güncelle
                if ($video->likes_count != $likesCount || $video->comments_count != $commentsCount) {
                    $video->update([
                        'likes_count' => $likesCount,
                        'comments_count' => $commentsCount
                    ]);
                    $updatedCount++;
                }
            }
        });
        
        Log::info("Video sayaçları reconciliation işlemi tamamlandı. {$updatedCount} video güncellendi.");
    }
}
