<?php

namespace App\Services\LiveStream;

use Exception;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Services\AgoraTokenService;
use App\Models\Agora\AgoraChannelInvite;
use App\Models\Agora\AgoraChannelViewer;
use App\Services\LiveStream\AgoraChannelService;
use App\Notifications\LiveStream\AgoraChannelInviteNotification;


class AgoraChannelInviteService
{
    protected AgoraChannelService $agoraChannelService;

    public function __construct(AgoraChannelService $agoraChannelService)
    {
        $this->agoraChannelService = $agoraChannelService;
    }

    /**
     * Kullanıcının yayına davet gönderme yetkisi olup olmadığını kontrol eder
     */
    private function checkUserCanInvite(AgoraChannel $stream, User $user): bool
    {
        // 1. Kanal sahibi her zaman davet gönderebilir
        if ($stream->user_id === $user->id) {
            return true;
        }

        // 2. Aktif izleyici ise davet gönderebilir (son 5 dakika içinde yayında olmuş)
        $activeViewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('user_id', $user->id)
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->where('joined_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($activeViewer) {
            return true;
        }

        // 3. Yayıncıyı takip ediyorsa davet gönderebilir
        $isFollowing = $user->followingUsers()->where('followed_user_id', $stream->user_id)->exists();
        if ($isFollowing) {
            return true;
        }

        return false;
    }

    public function inviteUserToChannel(array $input, User $authUser): AgoraChannelInvite
    {
        $agoraChannelId = $input['agora_channel_id'] ?? null;
        $invitedUserId = $input['invited_user_id'] ?? null;


        //1. Canlı yayın kontorlü?
        $stream = AgoraChannel::find($agoraChannelId);
        if (!$stream) {
            throw new Exception('Canlı yayın bulunamadı.');
        }

        if (!$stream->is_online) {
            // Host davet atıyorsa ve akış aktif görünüyor ise (frontend tarafında yayın açık),
            // Mongo'daki bayrak senkron olmamış olabilir. Host için otomatik düzeltme yapalım.
            if ($stream->user_id === $authUser->id) {
                $stream->is_online = true;
                $stream->save();
            } else {
                throw new Exception('Canlı yayın sonlandığı için konuk davet edilemez.');
            }
        }

        // Yeni: İzleyiciler ve takipçiler de davet gönderebilir
        $canInvite = $this->checkUserCanInvite($stream, $authUser);
        if (!$canInvite) {
            throw new Exception('Bu yayına davet gönderemezsiniz. Yayını takip edin veya aktif izleyici olun.');
        }

        //2. Konuk id geçerli mi?
        $invitedUser = User::find($invitedUserId);
        if (!$invitedUser) {
            throw new Exception('Konuk bulunamadı.');
        }

        //3. Aynı konuk için bekleyen bir davet isteği var mı son 3 dakika içinde?
        $pendingInviteExists = AgoraChannelInvite::where('agora_channel_id', $stream->id)
            ->where('user_id', $authUser->id)
            ->where('invited_user_id', $invitedUser->id)
            ->where('status_id', AgoraChannelInvite::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        if ($pendingInviteExists) {
            throw new Exception('Arka arkaya davet gönderemezsiniz. Bir süre sonra tekrar deneyin.');
        }

        //4. Yayın kapasitesi yeterli mi? (Yayında 4 yayıncı var ise daha fazla konuk eklenemez.)
        $viewerCount = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->count();

        if ($viewerCount >= 4) {
            throw new Exception('Yayın kapasitesi yeni konuk için yeterli değil.');
        }

        //5. Konuk daveti oluştur.
        $invite = AgoraChannelInvite::create([
            'agora_channel_id' => $stream->id,
            'user_id' => $authUser->id,
            'invited_user_id' => $invitedUser->id,
            'status_id' => AgoraChannelInvite::STATUS_PENDING,
        ]);

        if (!$invite) {
            throw new Exception('Sistemsel bir hata oluştu, davet oluşturulamadı.');
        }

        // Load the channel relationship before sending notification
        $invite->load('agoraChannel');

        //6. Davetliye bildirim gönder.
        $invitedUser->notify(new AgoraChannelInviteNotification($invite));

        return $invite;
    }

    public function respondToInvite(array $input, User $authUser): array
    {
        $inviteId = $input['invite_id'] ?? null;
        $response = $input['response'] ?? null;
        $createSeparateStream = $input['create_separate_stream'] ?? true; // Co-host için ayrı yayın oluştur

        //1. Davet kontrolü
        $invite = AgoraChannelInvite::find($inviteId);
        if (!$invite) {
            throw new Exception('Davet bulunamadı.');
        }

        if ($invite->invited_user_id !== $authUser->id) {
            throw new Exception('Bu davete cevap veremezsiniz.');
        }

        if ($invite->status_id !== AgoraChannelInvite::STATUS_PENDING) {
            throw new Exception('Davet zaten onaylandı.');
        }


        //2. Yayın kontrolü.
        /** @var \App\Models\Agora\AgoraChannel $stream */
        $stream = AgoraChannel::find($invite->agora_channel_id);
        if (!$stream) {
            throw new Exception('Canlı yayın bulunamadı.');
        }

        if (!$stream->is_online) {
            throw new Exception('Canlı yayın sonlandığı için katılamıyorsunuz.');
        }

        //3. Daveti onayla
        $sharedVideoRoomId = null;
        
        if ($response === true) {
            \Log::info('AgoraChannelInviteService: Accepting invitation', [
                'create_separate_stream' => $createSeparateStream,
                'stream_id' => $stream->id,
                'user_id' => $authUser->id
            ]);
            
            // Co-host için ayrı yayın oluştur
            if ($createSeparateStream) {
                try {
                    $cohostStream = $this->createCohostStream($stream, $authUser);
                    $sharedVideoRoomId = $cohostStream->shared_video_room_id;
                    
                    \Log::info('AgoraChannelInviteService: Co-host stream created', [
                        'cohost_stream_id' => $cohostStream->id,
                        'shared_video_room_id' => $sharedVideoRoomId
                    ]);
                    
                    // Ana yayına co-host channel ID'sini ekle
                    $cohostChannelIds = $stream->cohost_channel_ids ?? [];
                    $cohostChannelIds[] = $cohostStream->id;
                    $stream->cohost_channel_ids = $cohostChannelIds;
                    $stream->save();
                    
                    // Co-host'u ana yayına da guest olarak ekle (video paylaşımı için)
                    $viewer = $this->agoraChannelService->joinStream($stream, $authUser, AgoraChannelViewer::ROLE_GUEST, AgoraTokenService::RoleAttendee);
                    
                    $returnChannel = $cohostStream;
                } catch (\Exception $e) {
                    \Log::error('AgoraChannelInviteService: Error creating co-host stream', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            } else {
                // Sadece guest olarak katıl (eski yöntem)
                $viewer = $this->agoraChannelService->joinStream($stream, $authUser, AgoraChannelViewer::ROLE_GUEST, AgoraTokenService::RoleAttendee);
                $returnChannel = $stream;
                $sharedVideoRoomId = $stream->shared_video_room_id;
            }
        } else {
            // Reddedildi
            $viewer = null;
            $returnChannel = null;
        }

        $invite->update([
            'status_id' => $response === true ? AgoraChannelInvite::STATUS_ACCEPTED : AgoraChannelInvite::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        //4. Bu kanala ait katılma davetleri rejected olarak güncellenebilir. (job açıp queue da)
        //...

        $result = [
            'agora_channel' => $returnChannel,
            'token' => $viewer ? $viewer->token : null,
            'shared_video_room_id' => $response === true ? $sharedVideoRoomId : null,
            'parent_channel_id' => $response === true && $createSeparateStream ? $stream->id : null
        ];
        
        \Log::info('AgoraChannelInviteService: Final response', [
            'result' => $result,
            'shared_video_room_id' => $sharedVideoRoomId,
            'response' => $response,
            'createSeparateStream' => $createSeparateStream
        ]);
        
        return $result;
    }

    /**
     * Yayındaki aktif izleyicileri ve takipçileri davet edilebilir kullanıcı olarak listeler
     */
    public function getInvitableUsers(string $agoraChannelId, User $authUser): array
    {
        $stream = AgoraChannel::find($agoraChannelId);
        if (!$stream) {
            throw new Exception('Canlı yayın bulunamadı.');
        }

        // Davet gönderebiliyor mu kontrol et
        if (!$this->checkUserCanInvite($stream, $authUser)) {
            throw new Exception('Bu yayına davet gönderemezsiniz.');
        }

        $invitableUsers = [];

        // 1. Yayındaki aktif izleyiciler (son 10 dakika içinde aktif)
        $activeViewers = AgoraChannelViewer::where('agora_channel_id', $agoraChannelId)
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->where('joined_at', '>=', now()->subMinutes(10))
            ->whereNotIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST]) // Zaten yayında olmayanlar
            ->get();

        foreach ($activeViewers as $viewer) {
            if ($viewer->user_data && $viewer->user_id !== $authUser->id) {
                $invitableUsers[] = [
                    'id' => $viewer->user_id,
                    'type' => 'active_viewer',
                    'user_data' => $viewer->user_data,
                    'watch_duration' => $viewer->watch_duration ?? 0,
                    'joined_at' => $viewer->joined_at
                ];
            }
        }

        // 2. Yayıncının takipçileri (çevrimiçi olanlar tercihen)
        $streamOwner = User::find($stream->user_id);
        $streamerFollowers = $streamOwner
            ? $streamOwner->followers()
                ->where('users.id', '!=', $authUser->id)
                ->where('users.last_seen_at', '>=', now()->subMinutes(30)) // Son 30 dakikada aktif
                ->limit(20)
                ->get()
            : collect();

        foreach ($streamerFollowers as $follower) {
            $invitableUsers[] = [
                'id' => $follower->id,
                'type' => 'follower',
                'user_data' => [
                    'id' => $follower->id,
                    'name' => $follower->name,
                    'nickname' => $follower->nickname,
                    'avatar' => $follower->avatar,
                    'is_online' => $follower->last_seen_at >= now()->subMinutes(5)
                ]
            ];
        }

        // 3. Takipçilerin takipçileri (mutual friends)
        $mutualConnections = $this->getMutualConnections($authUser, $stream->user_id, 10);
        foreach ($mutualConnections as $connection) {
            $invitableUsers[] = [
                'id' => $connection->id,
                'type' => 'mutual_follower',
                'user_data' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'nickname' => $connection->nickname,
                    'avatar' => $connection->avatar,
                    'is_online' => $connection->last_seen_at >= now()->subMinutes(5)
                ]
            ];
        }

        // Duplikasyonları kaldır ve sırala
        $uniqueUsers = collect($invitableUsers)
            ->unique('id')
            ->sortByDesc(function ($user) {
                // Öncelik sırası: aktif izleyici > takipçi > mutual
                $priority = match ($user['type']) {
                    'active_viewer' => 3,
                    'follower' => 2,
                    'mutual_follower' => 1,
                    default => 0
                };
                return $priority;
            })
            ->take(50) // En fazla 50 kişi
            ->values()
            ->toArray();

        return $uniqueUsers;
    }
    
    /**
     * Co-host için ayrı yayın oluştur
     */
    private function createCohostStream(AgoraChannel $parentStream, User $cohostUser): AgoraChannel
    {
        // Co-host için yeni bir yayın oluştur
        $cohostStream = new AgoraChannel();
        $cohostStream->user_id = $cohostUser->id;
        $cohostStream->channel_name = $this->generateChannelName($cohostUser);
        $parentUser = User::find($parentStream->user_id);
        $cohostStream->title = $cohostUser->nickname . ' - Co-hosting with ' . ($parentUser?->nickname ?? 'Host');
        $cohostStream->description = 'Co-host stream for ' . $parentStream->title;
        $cohostStream->language_id = $parentStream->language_id;
        $cohostStream->is_online = true;
        $cohostStream->status_id = AgoraChannel::STATUS_LIVE;
        $cohostStream->stream_key = $this->generateStreamKey($cohostUser);
        $cohostStream->rtmp_url = config('agora.rtmp_url') . '/' . $cohostStream->stream_key;
        $cohostStream->playback_url = config('agora.playback_url') . '/' . $cohostStream->channel_name;
        $cohostStream->category_id = $parentStream->category_id;
        $cohostStream->tags = $parentStream->tags;
        $cohostStream->settings = $parentStream->settings;
        $cohostStream->started_at = now();
        
        // Co-host özel alanlar
        $cohostStream->is_cohost_stream = true;
        $cohostStream->parent_channel_id = $parentStream->id;
        
        // Ana stream'in shared_video_room_id'sini kontrol et ve gerekirse oluştur
        $sharedVideoRoomId = $parentStream->shared_video_room_id ?? $parentStream->channel_name;
        if (!$parentStream->shared_video_room_id) {
            $parentStream->shared_video_room_id = $sharedVideoRoomId;
            $parentStream->save();
        }
        
        $cohostStream->shared_video_room_id = $sharedVideoRoomId;
        
        // Mode'u co-host olarak işaretle
        $cohostStream->mode = 'cohost';
        
        $cohostStream->save();
        
        // Co-host'u kendi yayınına host olarak ekle
        AgoraChannelViewer::create([
            'agora_channel_id' => $cohostStream->id,
            'user_id' => $cohostUser->id,
            'role_id' => (int) AgoraChannelViewer::ROLE_HOST,
            'status_id' => (int) AgoraChannelViewer::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        
        // Token oluştur
        $cohostStream->token = $this->generateToken(
            $cohostStream->channel_name,
            $cohostUser->agora_uid,
            \App\Services\AgoraTokenService::RolePublisher
        );

        // CRITICAL: Register cohost relationship in related_streams table
        try {
            DB::table('related_streams')->insert([
                'host_stream_id' => $parentStream->channel_name,
                'cohost_stream_id' => $cohostStream->channel_name,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            \Log::info('AgoraChannelInviteService: Related streams registered', [
                'host_stream_id' => $parentStream->channel_name,
                'cohost_stream_id' => $cohostStream->channel_name,
                'cohost_user_id' => $cohostUser->id
            ]);
        } catch (\Exception $e) {
            \Log::error('AgoraChannelInviteService: Failed to register related streams', [
                'error' => $e->getMessage(),
                'host_stream_id' => $parentStream->channel_name,
                'cohost_stream_id' => $cohostStream->channel_name
            ]);
            // Don't fail the entire process, but log the error
        }

        return $cohostStream;
    }
    
    /**
     * Generate channel name for co-host
     */
    private function generateChannelName(User $user): string
    {
        return \Illuminate\Support\Str::slug($user->nickname) . '_cohost_' . now()->timestamp;
    }
    
    /**
     * Generate stream key for co-host
     */
    private function generateStreamKey(User $user): string
    {
        return \Illuminate\Support\Str::random(10) . '_' . $user->id . '_cohost_' . now()->timestamp;
    }
    
    /**
     * Generate Agora token
     */
    private function generateToken(string $channelName, string $userId, $role): string
    {
        $agoraTokenService = app(\App\Services\AgoraTokenService::class);
        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');
        $expirationTimeInSeconds = 3600;
        $currentTimestamp = time();
        $expireTimeInSeconds = $currentTimestamp + $expirationTimeInSeconds;
        
        return $agoraTokenService->buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $userId,
            $role,
            $expireTimeInSeconds,
            $currentTimestamp
        );
    }

    /**
     * Ortak bağlantıları bulur
     */
    private function getMutualConnections(User $user, string $streamerId, int $limit = 10)
    {
        return User::whereHas('followingUsers', function ($query) use ($streamerId) {
                $query->where('followed_user_id', $streamerId);
            })
            ->whereHas('followers', function ($query) use ($user) {
                $query->where('follower_user_id', $user->id);
            })
            ->where('id', '!=', $user->id)
            ->where('id', '!=', $streamerId)
            ->where('last_seen_at', '>=', now()->subMinutes(60))
            ->limit($limit)
            ->get();
    }
}
