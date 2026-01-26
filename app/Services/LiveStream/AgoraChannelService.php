<?php

namespace App\Services\LiveStream;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\BunnyCdnService;
use App\Models\Agora\AgoraChannel;
use App\Models\LiveStreamCategory;
use App\Models\Challenge\Challenge;
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
use App\Events\LiveStream\CohostLeft;
use App\Models\Agora\AgoraChannelViewer;
use Illuminate\Support\Facades\DB;
use App\Services\LiveStream\HostLeaveTransitionService;

class AgoraChannelService
{
    protected $appId;
    protected $appCertificate;
    protected $expirationTimeInSeconds;
    protected $agoraTokenService;
    protected $hostLeaveTransitionService;


    public function __construct(
        AgoraTokenService $agoraTokenService,
        protected BunnyCdnService $bunnyCdnService,
        ?HostLeaveTransitionService $hostLeaveTransitionService = null
    )
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
        $this->expirationTimeInSeconds = 3600; // 1 saat
        $this->agoraTokenService = $agoraTokenService;
        $this->hostLeaveTransitionService = $hostLeaveTransitionService ?? app(HostLeaveTransitionService::class);
    }

    public function startStream(User $user, array $data): ?AgoraChannel
    {
        try {
            // Punishment check
            if ($user->has_active_punishment) {
                throw new Exception('Cezalı olduğunuz için yayın yapamazsınız.');
            }

            // For cohost streams, skip the active stream check
            $isCohost = isset($data['is_cohost']) && $data['is_cohost'];

            if (!$isCohost) {
                // aktif yayını var mı kontrol et (only for regular streams)
                $activeStream = AgoraChannel::where('user_id', $user->id)
                    ->where('is_online', true)
                    ->first();
                if ($activeStream) {
                    throw new Exception('Zaten aktif bir yayınınız var.');
                }
            }

            // Yayın bilgileri hazırlama
            // Use provided channel ID for cohost, otherwise generate
            $channelName = isset($data['agora_channel_id']) ? $data['agora_channel_id'] : $this->generateChannelName($user);
            $streamKey = $this->generateStreamKey($user);

            // Kategori kontrolü
            $categoryId = $data['category_id'] ?? null;
            if ($categoryId) {
                $category = LiveStreamCategory::find($categoryId);
                if (!$category || !$category->is_active) {
                    throw new Exception('Kategori bulunamadı.');
                }
            }

            $settings = $data['settings'] ?? $this->getDefaultSettings();

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
            $stream->settings = $settings;
            $stream->started_at = now();
            
            // Ana stream için shared video room ID set et
            $stream->shared_video_room_id = $channelName;

            // Set cohost-related fields
            $stream->is_cohost = $isCohost;
            $stream->is_cohost_stream = $isCohost;

            Log::info('🎮 COHOST-CREATE: Setting cohost fields', [
                'isCohost' => $isCohost,
                'data_keys' => array_keys($data),
                'has_host_stream_id' => isset($data['host_stream_id']),
                'has_parent_stream_id' => isset($data['parent_stream_id']),
                'host_stream_id_value' => $data['host_stream_id'] ?? null,
                'parent_stream_id_value' => $data['parent_stream_id'] ?? null,
            ]);

            if ($isCohost && isset($data['host_stream_id'])) {
                $stream->host_stream_id = $data['host_stream_id'];
                $stream->parent_stream_id = $data['host_stream_id'];
                $stream->parent_channel_id = $data['host_stream_id'];
                Log::info('🎮 COHOST-CREATE: Set fields via host_stream_id', [
                    'host_stream_id' => $data['host_stream_id']
                ]);
            }
            if ($isCohost && isset($data['parent_stream_id'])) {
                $stream->parent_stream_id = $data['parent_stream_id'];
                $stream->parent_channel_id = $data['parent_stream_id'];
                Log::info('🎮 COHOST-CREATE: Set fields via parent_stream_id', [
                    'parent_stream_id' => $data['parent_stream_id']
                ]);
            }

            Log::info('🎮 COHOST-CREATE: Before save', [
                'is_cohost' => $stream->is_cohost,
                'is_cohost_stream' => $stream->is_cohost_stream,
                'parent_stream_id' => $stream->parent_stream_id,
                'parent_channel_id' => $stream->parent_channel_id,
                'host_stream_id' => $stream->host_stream_id,
            ]);

            $stream->save();

            Log::info('🎮 COHOST-CREATE: After save - verifying', [
                'stream_id' => $stream->id,
                'is_cohost' => $stream->is_cohost,
                'is_cohost_stream' => $stream->is_cohost_stream,
                'parent_stream_id' => $stream->parent_stream_id,
                'parent_channel_id' => $stream->parent_channel_id,
                'host_stream_id' => $stream->host_stream_id,
            ]);

            $stream->token = $this->generateToken($channelName, $user->agora_uid, AgoraTokenService::RolePublisher);

            // Register cohost relationship if this is a cohost stream
            if ($isCohost && isset($data['parent_stream_id'])) {
                DB::table('related_streams')->insert([
                    'host_stream_id' => $data['parent_stream_id'],
                    'cohost_stream_id' => $channelName,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // ✅ CRITICAL FIX: Add cohost ID to parent stream's cohost_channel_ids array
                // This enables parent stream messages to broadcast to all cohost channels
                $parentStream = AgoraChannel::find($data['parent_stream_id']);
                if ($parentStream) {
                    $cohostIds = $parentStream->cohost_channel_ids ?? [];
                    if (!in_array($stream->id, $cohostIds)) {
                        $cohostIds[] = $stream->id;
                        $parentStream->cohost_channel_ids = $cohostIds;
                        $parentStream->save();

                        Log::info('✅ Added cohost to parent stream cohost_channel_ids', [
                            'parent_stream_id' => $parentStream->id,
                            'cohost_stream_id' => $stream->id,
                            'total_cohosts' => count($cohostIds)
                        ]);
                    }
                }

                Log::info('Cohost stream registered', [
                    'host_stream_id' => $data['parent_stream_id'],
                    'cohost_stream_id' => $channelName,
                    'user_id' => $user->id
                ]);
            }

            //konuklara yayınıcıyı ekliyoruz
            AgoraChannelViewer::create([
                'agora_channel_id' => $stream->id,
                'user_id' => $user->id,
                'role_id' => (int) AgoraChannelViewer::ROLE_HOST,
                'status_id' => (int) AgoraChannelViewer::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            // Set initial heartbeat for host stream to prevent immediate timeout
            Cache::put("stream_heartbeat_{$stream->id}", now(), 120);
            Log::info('Initial heartbeat set for host stream', [
                'stream_id' => $stream->id,
                'user_id' => $user->id,
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
            // Check if stream is active - either LIVE status or is_online flag
            if ($stream->status_id !== AgoraChannel::STATUS_LIVE && !$stream->is_online) {
                throw new Exception('Yayın zaten kapalı.');
            }

            // Ana yayıncı veya cohost olup olmadığını kontrol et
            $isStreamOwner = $stream->user_id === $user->id;
            $cohostViewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
                ->where('user_id', $user->id)
                ->where('role_id', AgoraChannelViewer::ROLE_HOST)
                ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
                ->first();
            $isCohost = $cohostViewer !== null;

            if (!$isStreamOwner && !$isCohost) {
                throw new Exception('Bu yayın için heartbeat gönderme yetkiniz yok.');
            }

            // Ana yayıncı için heartbeat
            if ($isStreamOwner) {
                Cache::put("stream_heartbeat_{$stream->id}", now(), 90); // 90 saniye olarak artırdık
            }

            // Cohost için ayrı heartbeat
            if ($isCohost) {
                Cache::put("stream_heartbeat_{$stream->id}_cohost_{$user->id}", now(), 90);
                
                // Cohost'ların heartbeat listesini güncelle
                $cohostHeartbeats = Cache::get("stream_cohosts_{$stream->id}", []);
                $cohostHeartbeats[$user->id] = now();
                Cache::put("stream_cohosts_{$stream->id}", $cohostHeartbeats, 120);
                
                // Cohost viewer'ın last_activity_at'ini güncelle
                $cohostViewer->last_activity_at = now();
                $cohostViewer->save();
                
                // Redis'te de cohost'u aktif olarak işaretle
                Redis::sadd("agora_channel:{$stream->id}:active_cohosts", $user->id);
                Redis::expire("agora_channel:{$stream->id}:active_cohosts", 120); // 2 dakika TTL
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function endStream(AgoraChannel $stream)
    {
        try {
            // Check if stream is in LIVE status
            if ($stream->status_id !== AgoraChannel::STATUS_LIVE) {
                throw new Exception('Yayın zaten kapalı.');
            }

            $now = Carbon::now();

            $stream->is_online = false;
            $stream->status_id = AgoraChannel::STATUS_ENDED;
            $stream->ended_at = $now;
            $stream->duration = (int) $stream->started_at->diffInSeconds($now);
            $stream->save();

            // ✅ FIX: Her yayın bağımsız, cohost yayınlarını KAPATMA
            // Sadece parent'tan cohost ID'sini kaldır
            if (!$stream->is_cohost_stream) {
                // Ana yayın kapanıyorsa, cohostlara bildir ve bağımsız yayına geçir
                // 🔥 CRITICAL FIX: Use HostLeaveTransitionService to notify ALL cohosts
                $cohostCount = count($stream->cohost_channel_ids ?? []);

                Log::info('Host stream ending, checking for cohosts to transition', [
                    'stream_id' => $stream->id,
                    'cohost_count' => $cohostCount
                ]);

                if ($cohostCount > 0) {
                    try {
                        // Refresh stream to ensure we have latest data
                        $stream->refresh();

                        // Use HostLeaveTransitionService to notify all cohosts
                        // This will also dispatch StreamEnded WITH cohost IDs
                        $transitionResult = $this->hostLeaveTransitionService->handleHostLeave($stream);

                        Log::info('Host leave transition result', [
                            'stream_id' => $stream->id,
                            'result' => $transitionResult
                        ]);

                        // 🔥 CRITICAL FIX: Clean up and return early
                        // HostLeaveTransitionService already dispatched StreamEnded with cohost IDs
                        // Don't dispatch again at line 379 (which doesn't include cohost IDs)
                        Cache::forget("stream_cohosts_{$stream->id}");
                        Cache::forget("stream_heartbeat_{$stream->id}");
                        $cohosts = Redis::smembers("agora_channel:{$stream->id}:cohosts");
                        foreach ($cohosts as $cohostId) {
                            Cache::forget("stream_heartbeat_{$stream->id}_cohost_{$cohostId}");
                        }
                        Redis::del("agora_channel:{$stream->id}:cohosts");
                        Redis::del("agora_channel:{$stream->id}:active_cohosts");

                        Log::info('🔥 Host stream ended with cohosts - StreamEnded already dispatched by HostLeaveTransitionService', [
                            'stream_id' => $stream->id,
                        ]);
                        return; // Early return - don't dispatch StreamEnded again

                    } catch (\Exception $e) {
                        Log::error('Failed to handle host leave transition', [
                            'stream_id' => $stream->id,
                            'error' => $e->getMessage()
                        ]);
                        // Fall through to dispatch StreamEnded without cohost IDs as fallback
                    }
                } else {
                    // 🔥 CRITICAL FIX: Even if no active cohosts, check related_streams for formerly-related cohosts
                    // Viewers watching these cohosts should still receive the StreamEnded event
                    Log::info('Host stream ended, no active cohosts to transition - checking related_streams', [
                        'stream_id' => $stream->id,
                    ]);

                    // Find formerly-related cohost streams from related_streams table
                    $relatedCohostStreamIds = DB::table('related_streams')
                        ->where('host_stream_id', $stream->id)
                        ->pluck('cohost_stream_id')
                        ->filter()
                        ->toArray();

                    if (!empty($relatedCohostStreamIds)) {
                        Log::info('🔥 Found formerly-related cohost streams - will broadcast to them', [
                            'stream_id' => $stream->id,
                            'related_cohost_ids' => $relatedCohostStreamIds,
                        ]);

                        // Clean up and dispatch StreamEnded WITH related cohost IDs
                        Cache::forget("stream_cohosts_{$stream->id}");
                        Cache::forget("stream_heartbeat_{$stream->id}");
                        $cohosts = Redis::smembers("agora_channel:{$stream->id}:cohosts");
                        foreach ($cohosts as $cohostId) {
                            Cache::forget("stream_heartbeat_{$stream->id}_cohost_{$cohostId}");
                        }
                        Redis::del("agora_channel:{$stream->id}:cohosts");
                        Redis::del("agora_channel:{$stream->id}:active_cohosts");

                        // Dispatch StreamEnded WITH related cohost IDs
                        Event::dispatch(new StreamEnded($stream, $relatedCohostStreamIds));
                        return; // Early return
                    }
                }
            } else {
                // Co-host yayını kapanıyorsa, ana yayından co-host ID'sini kaldır
                $parentChannel = $stream->parentChannel();
                if ($parentChannel) {
                    $cohostChannelIds = $parentChannel->cohost_channel_ids ?? [];
                    $cohostChannelIds = array_filter($cohostChannelIds, function($id) use ($stream) {
                        return $id !== $stream->id;
                    });
                    $remainingCohostCount = count($cohostChannelIds);
                    $parentChannel->cohost_channel_ids = array_values($cohostChannelIds);
                    $parentChannel->save();

                    Log::info('Cohost stream ended, removed from parent', [
                        'cohost_stream_id' => $stream->id,
                        'parent_stream_id' => $parentChannel->id,
                        'remaining_cohost_count' => $remainingCohostCount
                    ]);

                    // 🔥 CRITICAL: Dispatch CohostLeft event to notify viewers
                    // Use parent channel's MongoDB ID for WebSocket channel (this is what viewer subscribes to)
                    $cohostUser = User::find($stream->user_id);
                    $cohostNickname = $cohostUser ? $cohostUser->nickname : 'Unknown';

                    Log::info('🔥🔥🔥 Dispatching CohostLeft event from endStream 🔥🔥🔥', [
                        'broadcast_channel_id' => $parentChannel->id,
                        'cohost_user_id' => $stream->user_id,
                        'cohost_nickname' => $cohostNickname,
                        'remaining_cohost_count' => $remainingCohostCount
                    ]);

                    Event::dispatch(new CohostLeft(
                        $parentChannel->id,  // Use parent's MongoDB ID for WebSocket channel
                        $stream->user_id ?? '',
                        $cohostNickname,
                        $remainingCohostCount
                    ));
                }
            }

            // Cohost cache'lerini temizle
            Cache::forget("stream_cohosts_{$stream->id}");
            Cache::forget("stream_heartbeat_{$stream->id}");

            // Tüm cohost heartbeat'lerini temizle
            $cohosts = Redis::smembers("agora_channel:{$stream->id}:cohosts");
            foreach ($cohosts as $cohostId) {
                Cache::forget("stream_heartbeat_{$stream->id}_cohost_{$cohostId}");
            }

            // Redis cohost setini temizle
            Redis::del("agora_channel:{$stream->id}:cohosts");
            Redis::del("agora_channel:{$stream->id}:active_cohosts");

            Event::dispatch(new StreamEnded($stream));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function joinStream(AgoraChannel $stream, User $user, $viewerroleId, $tokenRoleId): ?AgoraChannelViewer
    {
        try {
            // Cohost stream kontrolü - ana yayının token'ını kullan
            $channelNameForToken = $stream->channel_name;
            $actualStreamId = $stream->id;

            // Eğer bu bir cohost stream ise, ana yayının ID'sini kullan (Zego mixed stream için)
            if ($stream->is_cohost_stream && $stream->parent_channel_id) {
                $parentStream = AgoraChannel::find($stream->parent_channel_id);
                if ($parentStream) {
                    // CRITICAL: Zego için mixed stream parent'ın ID'sini kullanır
                    $channelNameForToken = $parentStream->id;
                    Log::info('Cohost stream viewer joining with parent ID for token', [
                        'cohost_stream_id' => $stream->id,
                        'parent_stream_id' => $parentStream->id,
                        'channel_for_token' => $channelNameForToken
                    ]);
                }
            }

            // Aktif izleyici kontrolü
            $activeViewer = AgoraChannelViewer::where('agora_channel_id', $actualStreamId)
                ->where('user_id', $user->id)
                ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
                ->first();

            if ($activeViewer) {
                // Token'ı güncelle (cohost stream için gerekli olabilir)
                $activeViewer->token = $this->generateToken($channelNameForToken, $user->agora_uid, $tokenRoleId);
                $activeViewer->save();
                return $activeViewer;
            }

            // Daha önce ayrılmış izleyici var mı kontrol et
            $leftViewer = AgoraChannelViewer::where('agora_channel_id', $actualStreamId)
                ->where('user_id', $user->id)
                ->where('status_id', AgoraChannelViewer::STATUS_LEFT)
                ->first();

            // Eğer daha önce ayrılmışsa, tekrar aktif yap
            if ($leftViewer) {
                $leftViewer->update([
                    'status_id' => AgoraChannelViewer::STATUS_ACTIVE,
                    'joined_at' => now(),
                    'left_at' => null,
                    'viewer_role_id' => $viewerroleId,
                    'token_role_id' => $tokenRoleId,
                    'token' => $this->generateToken($channelNameForToken, $user->agora_uid, $tokenRoleId),
                ]);

                // Redis'e tekrar ekle
                Redis::sadd("agora_channel:{$actualStreamId}:viewers", $user->id);
                Redis::incr("agora_channel:{$actualStreamId}:viewer_count");

                // ViewerJoined eventi dispatch et - leftViewer object olmalı, ID değil
                Event::dispatch(new ViewerJoined($leftViewer, $actualStreamId));

                return $leftViewer;
            }

            $token = $this->generateToken($channelNameForToken, $user->agora_uid, $tokenRoleId);

            $viewer = AgoraChannelViewer::create([
                'agora_channel_id' => $actualStreamId,
                'user_id' => $user->id,
                'token' => $token,
                'role_id' => $viewerroleId,
                'status_id' => AgoraChannelViewer::STATUS_ACTIVE,
                'joined_at' => now(),
                'is_following_streamer' => $user->isFollowing($stream->user_id),
            ]);

            //Redis kayıt işlemleri
            Redis::incr("agora_channel:{$actualStreamId}:viewer_count");
            Redis::sadd("agora_channel:{$actualStreamId}:viewers", $user->id); // Bu SET yapısı sayesinde aynı kullanıcı 2 kere yazılamaz.

            // İzleyici sayısını güncelle
            $this->updateViewerCount($stream);

            // İzleyici katılım olayı tetikleme
            Event::dispatch(new ViewerJoined($viewer));

            return $viewer;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function joinAsCohost(AgoraChannel $stream, User $user): array
    {
        try {
            // Check if stream is active - either LIVE status or is_online flag
            if ($stream->status_id !== AgoraChannel::STATUS_LIVE && !$stream->is_online) {
                throw new Exception('Yayın aktif değil.');
            }

            // Cohost zaten var mı kontrol et
            $existingCohost = AgoraChannelViewer::where('agora_channel_id', $stream->id)
                ->where('user_id', $user->id)
                ->where('role_id', AgoraChannelViewer::ROLE_HOST)
                ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
                ->first();

            if ($existingCohost) {
                // Token'ı yenile
                $existingCohost->token = $this->generateToken($stream->channel_name, $user->agora_uid, AgoraTokenService::RolePublisher);
                $existingCohost->save();

                return [
                    'success' => true,
                    'token' => $existingCohost->token,
                    'channel_name' => $stream->channel_name,
                    'agora_channel_id' => $stream->id,
                    'agora_uid' => $user->agora_uid
                ];
            }

            // Cohost için token oluştur
            $token = $this->generateToken($stream->channel_name, $user->agora_uid, AgoraTokenService::RolePublisher);

            // Cohost'u AgoraChannelViewer'a ekle
            $cohostViewer = AgoraChannelViewer::create([
                'agora_channel_id' => $stream->id,
                'user_id' => $user->id,
                'token' => $token,
                'role_id' => (int) AgoraChannelViewer::ROLE_HOST, // Cohost da host rolünde
                'status_id' => (int) AgoraChannelViewer::STATUS_ACTIVE,
                'joined_at' => now(),
                'is_following_streamer' => $user->isFollowing($stream->user_id),
            ]);

            // Redis kayıt işlemleri - cohost'ları ayrı tutabiliriz
            Redis::sadd("agora_channel:{$stream->id}:cohosts", $user->id);

            Log::info('Cohost joined stream', [
                'stream_id' => $stream->id,
                'cohost_user_id' => $user->id,
                'token_generated' => !empty($token)
            ]);

            return [
                'success' => true,
                'token' => $token,
                'channel_name' => $stream->channel_name,
                'agora_channel_id' => $stream->id,
                'agora_uid' => $user->agora_uid
            ];

        } catch (Exception $e) {
            Log::error('Failed to join as cohost', [
                'stream_id' => $stream->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

            $isCohost = $viewer->role_id === AgoraChannelViewer::ROLE_HOST && $stream->user_id !== $user->id;

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
            if ($isCohost) {
                Redis::srem("agora_channel:{$stream->id}:cohosts", $user->id);
                Redis::srem("agora_channel:{$stream->id}:active_cohosts", $user->id);
                
                // Cohost heartbeat'ini temizle
                Cache::forget("stream_heartbeat_{$stream->id}_cohost_{$user->id}");
                
                // Cohost listesinden çıkar
                $cohostHeartbeats = Cache::get("stream_cohosts_{$stream->id}", []);
                unset($cohostHeartbeats[$user->id]);
                Cache::put("stream_cohosts_{$stream->id}", $cohostHeartbeats, 120);
                
                Log::info('Cohost left stream', [
                    'stream_id' => $stream->id,
                    'cohost_user_id' => $user->id
                ]);
            } else {
                Redis::srem("agora_channel:{$stream->id}:viewers", $user->id);
                Redis::decr("agora_channel:{$stream->id}:viewer_count");
                
                // İzleyici sayısını güncelle
                $this->updateViewerCount($stream);
            }

            Event::dispatch(new ViewerLeft($stream->id, $viewer));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function likeStream(AgoraChannel $agoraChannel, User $user)
    {
        try {
            \Log::info('🔴 AgoraChannelService: likeStream called for channel: ' . $agoraChannel->id . ' by user: ' . $user->id);
            
            // Beğeni sayısını redis'e kaydet
            $redisKey = "agora_channel:{$agoraChannel->id}:likes";
            $newLikeCount = Redis::INCR($redisKey);
            \Log::info('✅ AgoraChannelService: Redis like count incremented to: ' . $newLikeCount . ' for key: ' . $redisKey);

            \Log::info('🔴 AgoraChannelService: Dispatching StreamLiked event using Event facade...');
            \Illuminate\Support\Facades\Event::dispatch(new StreamLiked($agoraChannel, $user));
            \Log::info('✅ AgoraChannelService: StreamLiked event dispatched successfully');
            
        } catch (Exception $e) {
            \Log::error('❌ AgoraChannelService: likeStream error: ' . $e->getMessage());
            \Log::error('❌ Stack trace: ' . $e->getTraceAsString());
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
                $stream->settings = $data['settings'];
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
        
        // Co-host yayınları dahil etme filtresi (varsayılan: true)
        $includeCohostStreams = $filters['include_cohost_streams'] ?? true;
        
        // Co-host yayınları dahil edilmeyecekse filtrele
        if (!$includeCohostStreams) {
            $query->where(function($q) {
                $q->whereNull('is_cohost_stream')
                  ->orWhere('is_cohost_stream', false);
            });
        }

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
