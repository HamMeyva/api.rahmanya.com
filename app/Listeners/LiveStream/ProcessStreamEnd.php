<?php

namespace App\Listeners\LiveStream;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\LiveStream\StreamEnded;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelViewer;
use App\Services\LiveStream\LiveStreamAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessStreamEnd implements ShouldQueue
{
    public function __construct(protected LiveStreamAnalyticsService $analyticsService) {}

    public function handle(StreamEnded $event): void
    {
        $stream = $event->stream;
        if (!$stream) return;

        try {
            // Redis'ten aktif yayınlar listesinden kaldır
            $this->removeFromActiveStreamsList($stream);

            // Beğenileri MongoDB'ye kaydet
            $this->analyticsService->syncLikesToDb($stream); //istatistik oluşturmadan önce çalışsın ! 

            // İstatistik oluştur
            $this->analyticsService->saveStreamStatistics($stream);

            // Tüm izleyici kayıtlarını güncelle
            $this->analyticsService->updateViewerRecords($stream);
        } catch (Exception $e) {
            Log::error('Error processing stream end: ' . $e->getMessage(), [
                'stream_id' => $stream->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function removeFromActiveStreamsList(AgoraChannel $stream)
    {
        // Aktif yayın listesinden kaldır
        Cache::forget('active_stream:' . $stream->id);

        // Kategoriye göre aktif yayınlar listesinden kaldır
        if ($stream->category_id) {
            $cacheKey = 'active_streams:category:' . $stream->category_id;
            $activeStreams = Cache::get($cacheKey, []);
            unset($activeStreams[$stream->id]);
            Cache::put($cacheKey, $activeStreams, now()->addDay());
        }

        // Önerilen yayınlar listesinden kaldır
        $cacheKey = 'featured_streams';
        $featuredStreams = Cache::get($cacheKey, []);
        unset($featuredStreams[$stream->id]);
        Cache::put($cacheKey, $featuredStreams, now()->addDay());
    }
}
