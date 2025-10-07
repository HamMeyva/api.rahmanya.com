<?php

namespace App\Services\LiveStream;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\BunnyCdnService;
use App\Models\Agora\AgoraChannel;
use App\Models\LiveStreamCategory;
use App\Services\AgoraTokenService;
use Illuminate\Support\Facades\Log;
use App\Models\Demographic\Language;
use App\Events\LiveStream\ViewerLeft;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use App\Events\LiveStream\StreamEnded;
use App\Events\LiveStream\StreamLiked;
use App\Events\LiveStream\ViewerJoined;
use App\Events\LiveStream\StreamStarted;
use App\Events\LiveStream\StreamUpdated;
use App\Models\Agora\AgoraChannelViewer;

class AgoraChannelService
{
    protected $appId;
    protected $appCertificate;
    protected $expirationTimeInSeconds;
    protected $agoraTokenService;


    public function __construct(AgoraTokenService $agoraTokenService, protected BunnyCdnService $bunnyCdnService)
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
        $this->expirationTimeInSeconds = 3600; // 1 saat
        $this->agoraTokenService = $agoraTokenService;
    }

    public function startStream(User $user, array $data): ?AgoraChannel
    {
        try {
            // Punishment check
            if ($user->has_active_punishment) {
                throw new Exception('Cezalı olduğunuz için yayın yapamazsınız.');
            }

            // aktif yayını var mı kontrol et
            $activeStream = AgoraChannel::where('user_id', $user->id)
                ->where('is_online', true)
                ->first();
            if ($activeStream) {
                throw new Exception('Zaten aktif bir yayınınız var.');
            }
            // Yayın bilgileri hazırlama
            $streamKey = $this->generateStreamKey($user);
            $channelName = $this->generateChannelName($user);

            // Kategori kontrolü
            $categoryId = $data['category_id'] ?? null;
            if ($categoryId) {
                $category = LiveStreamCategory::find($categoryId);
                if (!$category || !$category->is_active) {
                    throw new Exception('Kategori bulunamadı.');
                }
            }

            // AgoraChannel oluşturma
            $stream = new AgoraChannel();
            $stream->user_id = $user->id;
            $stream->channel_name = $channelName;
            $stream->title = $data['title'] ?? $user->nickname . '\'s Stream';
            $stream->description = $data['description'] ?? '';
            $stream->language_id = $data['language_id'] ?? Language::TR;
            $stream->is_online = true;
            $stream->status_id = AgoraChannel::STATUS_LIVE;
            $stream->stream_key = $streamKey;
            $stream->rtmp_url = config('agora.rtmp_url') . '/' . $streamKey;
            $stream->playback_url = config('agora.playback_url') . '/' . $channelName;
            $stream->category_id = $categoryId;
            $stream->thumbnail_url = $data['thumbnail_url'] ?? null;
            $stream->tags = $data['tags'] ?? [];
            $stream->settings = $data['settings'] ?? $this->getDefaultSettings();
            $stream->started_at = now();
            $stream->save();

            $stream->token = $this->generateToken($channelName, $user->id, AgoraTokenService::RolePublisher);


            //konuklara yayınıcıyı ekliyoruz
            AgoraChannelViewer::create([
                'agora_channel_id' => $stream->id,
                'user_id' => $user->id,
                'role_id' => (int) AgoraChannelViewer::ROLE_HOST,
                'status_id' => (int) AgoraChannelViewer::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            // Yayın başlatma olayı tetikleme
            Event::dispatch(new StreamStarted($stream));

            return $stream;
        } catch (Exception $e) {
            Log::error('Failed to start stream', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function streamHeartbeat(AgoraChannel $stream, User $user)
    {
        try {
            if (!$stream->is_online) {
                throw new Exception('Yayın zaten kapalı.');
            }

            if ($stream->user_id !== $user->id) {
                throw new Exception('You are not authorized to manage this stream');
            }

            Cache::put("stream_heartbeat_{$stream->id}", now(), 60);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function endStream(AgoraChannel $stream)
    {
        /*try {
            if (!$stream->is_online) {
                throw new Exception('Yayın zaten kapalı.');
            }

            $now = Carbon::now();

            $stream->is_online = false;
            $stream->status_id = AgoraChannel::STATUS_ENDED;
            $stream->ended_at = $now;
            $stream->duration = (int) $stream->started_at->diffInSeconds($now);
            $stream->save();

            Event::dispatch(new StreamEnded($stream));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }*/
    }

    public function joinStream(AgoraChannel $stream, User $user, $viewerroleId, $tokenRoleId): ?AgoraChannelViewer
    {
        try {
            // Zaten izliyor mu kontrol et
            $existingViewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
                ->where('user_id', $user->id)
                ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
                ->first();

            if ($existingViewer) {
                throw new Exception('Zaten yayına katılmışsınız.');
            }

            $token = $this->generateToken($stream->channel_name, $user->id, $tokenRoleId);

            $viewer = AgoraChannelViewer::create([
                'agora_channel_id' => $stream->id,
                'user_id' => $user->id,
                'token' => $token,
                'role_id' => $viewerroleId,
                'status_id' => AgoraChannelViewer::STATUS_ACTIVE,
                'joined_at' => now(),
                'is_following_streamer' => $user->isFollowing($stream->user_id),
            ]);

            //Redis kayıt işlemleri
            Redis::incr("agora_channel:{$stream->id}:viewer_count");
            Redis::sadd("agora_channel:{$stream->id}:viewers", $user->id); // Bu SET yapısı sayesinde aynı kullanıcı 2 kere yazılamaz.

            // İzleyici sayısını güncelle
            $this->updateViewerCount($stream);

            // İzleyici katılım olayı tetikleme
            Event::dispatch(new ViewerJoined($viewer));

            return $viewer;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function leaveStream(AgoraChannel $stream, User $user): void
    {
        try {
            // Aktif izleyiciyi bul
            $viewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
                ->where('user_id', $user->id)
                ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
                ->first();

            if (!$viewer) {
                throw new Exception('Yayında değilsiniz.');
            }

            // İzleme süresini hesapla
            $joinedAt = $viewer->joined_at;
            $leftAt = now();
            $watchDuration = $joinedAt ? $leftAt->diffInSeconds($joinedAt) : 0;

            // İzleyiciyi güncelle
            $viewer->update([
                'status_id' => AgoraChannelViewer::STATUS_LEFT,
                'left_at' => $leftAt,
                'watch_duration' => $watchDuration,
            ]);

            // Redis'ten kullanıcıyı çıkar
            Redis::srem("agora_channel:{$stream->id}:viewers", $user->id);
            Redis::decr("agora_channel:{$stream->id}:viewer_count");

            // İzleyici sayısını güncelle
            $this->updateViewerCount($stream);

            Event::dispatch(new ViewerLeft($stream->id, $viewer));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function likeStream(AgoraChannel $agoraChannel, User $user)
    {
        try {
            // Beğeni sayısını redis'e kaydet
            $redisKey = "agora_channel:{$agoraChannel->id}:likes";
            Redis::INCR($redisKey);

            event(new StreamLiked($agoraChannel, $user));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function screenShoot(AgoraChannel $agoraChannel, $media)
    {
        try {
            $thumbnailName = Str::uuid() . '.' . $media->extension();
            $mediaPath = "stream/{$agoraChannel->id}/thumbnails/{$thumbnailName}";

            $this->bunnyCdnService->uploadToStorage($mediaPath, $media->get());

            $agoraChannel->thumbnail_path = $mediaPath;
            $agoraChannel->save();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    protected function generateToken(string $channelName, string $userId, $role): string
    {
        $expireTimeInSeconds = time() + $this->expirationTimeInSeconds;
        $currentTimestamp = time();

        return $this->agoraTokenService->buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $userId,
            $role,
            $expireTimeInSeconds,
            $currentTimestamp
        );
    }

    protected function updateViewerCount(AgoraChannel $stream): void
    {
        $count = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->where('role_id', AgoraChannelViewer::ROLE_VIEWER)
            ->count();

        $stream->viewer_count = $count;

        // Maksimum izleyici sayısını güncelle
        if ($count > $stream->max_viewer_count) {
            $stream->max_viewer_count = $count;
        }

        $stream->save();
    }









    /**
     * Canlı yayını aktif duruma geçirir
     *
     * @param AgoraChannel $stream
     * @return bool
     */
    public function goLive(AgoraChannel $stream): bool
    {
        try {
            $stream->status_id = AgoraChannel::STATUS_LIVE;
            $stream->started_at = now();
            $stream->save();

            // Takipçilere bildirim gönder
            $this->notifyFollowers($stream);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to go live', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }



    /**
     * Canlı yayın bilgilerini günceller
     *
     * @param AgoraChannel $stream
     * @param array $data
     * @return bool
     */
    public function updateStream(AgoraChannel $stream, array $data): bool
    {
        try {
            // Güncellenebilir alanlar
            if (isset($data['title'])) {
                $stream->title = $data['title'];
            }

            if (isset($data['description'])) {
                $stream->description = $data['description'];
            }

            if (isset($data['category_id'])) {
                $category = LiveStreamCategory::find($data['category_id']);
                if ($category && $category->is_active) {
                    $stream->category_id = $data['category_id'];
                }
            }

            if (isset($data['tags'])) {
                $stream->tags = $data['tags'];
            }

            if (isset($data['thumbnail_url'])) {
                $stream->thumbnail_url = $data['thumbnail_url'];
            }

            if (isset($data['settings'])) {
                $stream->settings = array_merge($stream->settings, $data['settings']);
            }

            $stream->save();

            // Yayın güncelleme olayı tetikleme
            Event::dispatch(new StreamUpdated($stream));

            return true;
        } catch (Exception $e) {
            Log::error('Failed to update stream', [
                'stream_id' => $stream->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Kanal ismi oluşturur
     *
     * @param User $user
     * @return string
     */
    protected function generateChannelName(User $user): string
    {
        return Str::slug($user->nickname) . '_' . now()->timestamp;
    }

    /**
     * Stream anahtarı oluşturur
     *
     * @param User $user
     * @return string
     */
    protected function generateStreamKey(User $user): string
    {
        return Str::random(10) . '_' . $user->id . '_' . now()->timestamp;
    }

    /**
     * Takipçilere bildirim gönderir
     *
     * @param AgoraChannel $stream
     * @return void
     */
    protected function notifyFollowers(AgoraChannel $stream): void
    {
        // Bildirim sistemi entegrasyonu
        // Burada FCM, Pusher veya Reverb ile bildirim gönderilebilir
    }

    /**
     * Varsayılan yayın ayarlarını döndürür
     *
     * @return array
     */
    protected function getDefaultSettings(): array
    {
        return [
            'allow_comments' => true,
            'allow_gifts' => true,
            'auto_record' => true,
            'min_age_requirement' => 13,
            'blocked_words' => [],
            'moderator_users' => [],
            'stream_quality' => 'auto'
        ];
    }

    /**
     * Tüm aktif yayınları getirir
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getActiveStreams(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AgoraChannel::active();

        // Kategori filtresi
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->byCategory($filters['category_id']);
        }

        // Dil filtresi
        if (isset($filters['language']) && $filters['language']) {
            $query->where('language', $filters['language']);
        }

        // Öne çıkan yayınlar
        if (isset($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        // Takip edilen kullanıcılar
        if (isset($filters['following_user_id']) && $filters['following_user_id']) {
            // FollowService kullanarak takip edilenleri getir (FIXME: FollowService sınıfını oluştur veya entegre et)
            $followingIds = [];
            // $followingIds = app(FollowService::class)->getFollowingIds($filters['following_user_id']);

            $query->whereIn('user_id', $followingIds);
        }

        // Arama filtresi
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
            });
        }

        // Sıralama
        $orderBy = $filters['order_by'] ?? 'viewer_count';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        // Sayfalama parametreleri
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 15;

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Kullanıcının yayınlarını getirir
     *
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserStreams(int $userId, int $page = 1, int $limit = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AgoraChannel::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }


    /**
     * Yayının izleyicilerini getirir
     *
     * @param string $streamId
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getStreamViewers(string $streamId, int $page = 1, int $limit = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AgoraChannelViewer::where('agora_channel_id', $streamId)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->orderBy('last_activity_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }
}
