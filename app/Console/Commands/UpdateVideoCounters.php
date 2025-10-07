<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoComment;
use App\Models\VideoLike;
use Illuminate\Console\Command;

class UpdateVideoCounters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:update-counters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tüm videoların beğeni ve yorum sayaçlarını günceller';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Video sayaçları güncelleme işlemi başlatıldı...');
        
        $count = 0;
        $total = Video::count();
        
        $this->output->progressStart($total);
        
        // Tüm videoları 100'er 100'er işle
        Video::chunk(100, function ($videos) use (&$count) {
            foreach ($videos as $video) {
                // Gerçek beğeni ve yorum sayılarını hesapla
                $likesCount = VideoLike::where('video_id', $video->_id)->count();
                $commentsCount = VideoComment::where('video_id', $video->_id)->count();
                
                // Sayaçları güncelle
                $video->update([
                    'likes_count' => $likesCount,
                    'comments_count' => $commentsCount
                ]);
                
                $count++;
                $this->output->progressAdvance();
            }
        });
        
        $this->output->progressFinish();
        $this->info("İşlem tamamlandı! {$count} video güncellendi.");
        
        return Command::SUCCESS;
    }
}
