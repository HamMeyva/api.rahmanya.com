<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgoraChannelViewerResource;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelViewer;
use App\Services\LiveStream\AgoraChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LiveStreamViewerController extends Controller
{
    /**
     * @var AgoraChannelService
     */
    protected $channelService;

    /**
     * LiveStreamViewerController constructor.
     *
     * @param AgoraChannelService $channelService
     */
    public function __construct(AgoraChannelService $channelService)
    {
        $this->channelService = $channelService;
    }

    /**
     * Yayındaki izleyicileri listeler
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($streamId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        
        $viewers = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'viewers' => AgoraChannelViewerResource::collection($viewers),
                'total_count' => $viewers->count()
            ]
        ]);
    }

    /**
     * Aktif izleyici sayısını döndürür
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function count($streamId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        
        $count = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'viewer_count' => $count
            ]
        ]);
    }
    
    /**
     * Yayın sırasında izleyici durumunu ping eder
     * Bu, izleyicinin hala aktif olduğunu bildirmek için kullanılır
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping($streamId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        $user = Auth::user();
        
        // İzleyici bul
        $viewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('user_id', $user->id)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->first();
            
        if (!$viewer) {
            // Eğer izleyici kaydı yoksa veya aktif değilse, yeni katılım oluştur
            $deviceInfo = request()->input('device_info', []);
            $viewer = $this->channelService->addViewer($stream, $user, $deviceInfo);
            
            if (!$viewer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to join stream'
                ], 500);
            }
        }
        
        // Ping zamanını güncelle (viewer_ping alanını ekleyin)
        $viewer->viewer_ping = now();
        $viewer->save();
        
        return response()->json([
            'success' => true
        ]);
    }
    
    /**
     * İzleyicinin detay bilgilerini getirir
     *
     * @param string $streamId
     * @param string $viewerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($streamId, $viewerId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        
        $viewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('_id', $viewerId)
            ->firstOrFail();
        
        // Yetki kontrolü - sadece yayıncı veya moderatör detayları görebilir
        $user = Auth::user();
        $settings = $stream->settings ?? [];
        $moderators = $settings['moderator_users'] ?? [];
        
        if ($user->id !== $stream->user_id && !in_array($user->id, $moderators)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelViewerResource($viewer)
        ]);
    }
    
    /**
     * Yayındaki kendi izleyici durumunu getirir
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function myStatus($streamId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        $user = Auth::user();
        
        // İzleyici bul
        $viewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$viewer) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_viewer' => false,
                    'status' => null
                ]
            ]);
        }
        
        // Kullanıcı yayıncı mı kontrol et
        $isBroadcaster = ($user->id === $stream->user_id);
        
        // Moderatör mü kontrol et
        $settings = $stream->settings ?? [];
        $moderators = $settings['moderator_users'] ?? [];
        $isModerator = in_array($user->id, $moderators);
        
        // Bloklu mu kontrol et
        $blockedUsers = $settings['blocked_users'] ?? [];
        $isBlocked = in_array($user->id, $blockedUsers);

        return response()->json([
            'success' => true,
            'data' => [
                'is_viewer' => ($viewer->status === AgoraChannelViewer::STATUS_ACTIVE),
                'status' => $viewer->status,
                'is_broadcaster' => $isBroadcaster,
                'is_moderator' => $isModerator,
                'is_blocked' => $isBlocked,
                'joined_at' => $viewer->joined_at->toDateTimeString(),
                'left_at' => $viewer->left_at ? $viewer->left_at->toDateTimeString() : null,
                'watch_duration' => $viewer->watch_duration,
                'messages_count' => $viewer->messages_count,
                'gifts_sent' => $viewer->gifts_sent,
                'coins_spent' => $viewer->coins_spent
            ]
        ]);
    }
    
    /**
     * İzleyiciyi yasaklar
     *
     * @param Request $request
     * @param string $streamId
     * @param string $viewerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function ban(Request $request, $streamId, $viewerId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        $viewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('_id', $viewerId)
            ->firstOrFail();
        
        // Yetki kontrolü - sadece yayıncı veya moderatör yasaklayabilir
        $user = Auth::user();
        $settings = $stream->settings ?? [];
        $moderators = $settings['moderator_users'] ?? [];
        
        if ($user->id !== $stream->user_id && !in_array($user->id, $moderators)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Yayıncıyı yasaklayamazsın
        if ($viewer->user_id === $stream->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot ban the broadcaster'
            ], 400);
        }
        
        // Moderatörü yasaklayamazsın (eğer sen de moderatörsen)
        if (in_array($viewer->user_id, $moderators) && $user->id !== $stream->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot ban a moderator'
            ], 400);
        }
        
        // İzleyiciyi yasakla
        $viewer->status = AgoraChannelViewer::STATUS_BANNED;
        $viewer->left_at = now();
        $viewer->save();
        
        // Stream ayarlarında da engelle
        $blockedUsers = $settings['blocked_users'] ?? [];
        
        if (!in_array($viewer->user_id, $blockedUsers)) {
            $blockedUsers[] = $viewer->user_id;
            $settings['blocked_users'] = $blockedUsers;
            $stream->settings = $settings;
            $stream->save();
        }
        
        // Engelleme olayı tetikle
        event(new \App\Events\LiveStream\UserBlockedFromStream($stream, $viewer->user_id, $user->id));

        return response()->json([
            'success' => true
        ]);
    }
}
