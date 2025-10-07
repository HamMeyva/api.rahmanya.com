<?php

namespace App\Listeners\LiveStream;

use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use App\Events\LiveStream\StreamStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\LiveStream\LiveStreamStartedNotification;
use Illuminate\Support\Facades\Notification;

class ProcessStreamStart implements ShouldQueue
{
    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param NotificationService $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param StreamStarted $event
     * @return void
     */
    public function handle(StreamStarted $event)
    {
        try {
            // Redis'e aktif yayınlar listesine ekle
            $this->addToActiveStreamsList($event->stream);

            // Takipçilere bildirim gönder
            $this->sendNotificationsToFollowers($event->stream);

            // Yayını önerilen yayınlar listesine ekle (eğer gerekli kriterleri sağlıyorsa)
            $this->addToFeaturedStreamsIfEligible($event->stream);

            Log::info('Stream started processing completed', [
                'stream_id' => $event->stream->id,
                'user_id' => $event->stream->user_id,
                'status' => $event->stream->status
            ]);
        } catch (Exception $e) {
            Log::error('Error processing stream start', [
                'stream_id' => $event->stream->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Yayını Redis'teki aktif yayınlar listesine ekler
     *
     * @param \App\Models\Agora\AgoraChannel $stream
     * @return void
     */
    protected function addToActiveStreamsList($stream)
    {
        // Aktif yayınlar listesine ekle
        Cache::put('active_stream:' . $stream->id, [
            'id' => $stream->id,
            'user_id' => $stream->user_id,
            'title' => $stream->title,
            'thumbnail_url' => $stream->thumbnail_url,
            'category_id' => $stream->category_id,
            'viewer_count' => 0,
            'started_at' => $stream->started_at->timestamp,
            'featured' => (bool) $stream->is_featured
        ], now()->addDays(1));

        // Kategoriye göre aktif yayınlar listesine ekle
        if ($stream->category_id) {
            $cacheKey = 'active_streams:category:' . $stream->category_id;
            $activeStreams = Cache::get($cacheKey, []);
            $activeStreams[$stream->id] = $stream->id;
            Cache::put($cacheKey, $activeStreams, now()->addDays(1));
        }

        // Kullanıcının tüm yayınları listesine ekle
        $cacheKey = 'user_streams:' . $stream->user_id;
        $userStreams = Cache::get($cacheKey, []);
        $userStreams[$stream->id] = $stream->id;
        Cache::put($cacheKey, $userStreams, now()->addDays(7));
    }

    /**
     * Takipçilere bildirim gönderir
     *
     * @param \App\Models\Agora\AgoraChannel $stream
     * @return void
     */
    protected function sendNotificationsToFollowers($stream)
    {
        // Kullanıcının takipçilerini bul
        $user = User::find($stream->user_id);

        if (!$user) {
            Log::warning('User not found for stream', [
                'stream_id' => $stream->id,
                'user_id' => $stream->user_id
            ]);
            return;
        }

        Log::info('LiveStreamStartedNotification: Sending LiveStreamStartedNotification to followers.', [
            'stream_id' => $stream->id,
            'user_id' => $user->id,
        ]);

        $notification = new LiveStreamStartedNotification($stream);

        $user->followers()->chunkById(500, function ($chunk, $page) use ($notification) {
            $followers = $chunk->pluck('follower')->filter();

            Log::info("LiveStreamStartedNotification: Sending notifications to followers in chunk", [
                'recipient_count' => $followers->count(),
            ]);

            Notification::send($followers, $notification);

            Log::info("LiveStreamStartedNotification: Notifications sent successfully to chunk", [
                'chunk_number' => $page,
            ]);
        });
    }

    /**
     * Eğer yayın belirli kriterleri sağlıyorsa önerilen yayınlar listesine ekler
     *
     * @param \App\Models\Agora\AgoraChannel $stream
     * @return void
     */
    protected function addToFeaturedStreamsIfEligible($stream)
    {
        // Yayın zaten öneriliyorsa
        if ($stream->is_featured) {
            $this->addToFeaturedList($stream->id);
            return;
        }

        $user = User::find($stream->user_id);

        // Yayıncı popüler mi veya doğrulanmış mı kontrol et
        if ($user && ($user->is_verified || $user->followers_count > 1000)) {
            $this->addToFeaturedList($stream->id);
            return;
        }

        // Kategorisi özel mi kontrol et
        if ($stream->category_id && in_array($stream->category_id, config('livestream.featured_categories', []))) {
            $this->addToFeaturedList($stream->id);
            return;
        }
    }

    /**
     * Redis'teki önerilen yayınlar listesine ekler
     *
     * @param string $streamId
     * @return void
     */
    protected function addToFeaturedList($streamId)
    {
        $cacheKey = 'featured_streams';
        $featuredStreams = Cache::get($cacheKey, []);
        $featuredStreams[$streamId] = now()->timestamp;
        Cache::put($cacheKey, $featuredStreams, now()->addDays(1));
    }
}
