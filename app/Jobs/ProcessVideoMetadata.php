<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Video;
use App\Services\VideoService;
use App\Events\VideoProcessed;
use App\Events\VideoProcessingFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $videoId;
    protected $metadata;
    protected $attempts = 0;
    protected $maxAttempts = 3;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $videoId
     * @param array $metadata
     * @return void
     */
    public function __construct(User $user, string $videoId, array $metadata)
    {
        $this->user = $user;
        $this->videoId = $videoId;
        $this->metadata = $metadata;
        
        // Yüksek öncelikli queue kullan
        $this->onQueue('videos');
    }

    /**
     * Execute the job.
     *
     * @param VideoService $videoService
     * @return void
     */
    public function handle(VideoService $videoService)
    {
        try {
            // Video metadata işleme
            $result = $videoService->processVideoMetadata(
                $this->user,
                $this->videoId,
                $this->metadata
            );
            
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            
            // İşlem başarılı olduğunda, GraphQL abonelerine bildirim gönder
            if (isset($result['data']['video'])) {
                event(new VideoProcessed($result['data']['video']));
            }
            
            Log::info('Video metadata başarıyla işlendi', [
                'video_id' => $this->videoId,
                'user_id' => $this->user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Video metadata işleme hatası', [
                'video_id' => $this->videoId,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts
            ]);
            
            // Belirli bir sayıdan sonra yeniden deneme yapma
            $this->attempts++;
            if ($this->attempts < $this->maxAttempts) {
                $this->release(30 * $this->attempts); // Artan bekleme süresi
            } else {
                // Başarısız olduğunda bildirim gönder
                // Create a temporary Video object with minimal data for the event
                $tempVideo = new Video([
                    'user_id' => $this->user->id,
                    'video_guid' => $this->videoId,
                    'status' => 'failed',
                    'processing_status' => 'failed',
                    'is_sport' => $this->metadata['is_sport'] ?? false
                ]);
                
                event(new VideoProcessingFailed($tempVideo, $e->getMessage()));
            }
        }
    }
}
