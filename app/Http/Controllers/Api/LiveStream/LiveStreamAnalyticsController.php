<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Models\Agora\AgoraChannel;
use App\Models\User;
use App\Services\LiveStream\LiveStreamAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveStreamAnalyticsController extends Controller
{
    /**
     * @var LiveStreamAnalyticsService
     */
    protected $analyticsService;

    /**
     * LiveStreamAnalyticsController constructor.
     *
     * @param LiveStreamAnalyticsService $analyticsService
     */
    public function __construct(LiveStreamAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Kullanıcının yayın istatistiklerini getirir
     *
     * @param Request $request
     * @param int|null $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStats(Request $request, $userId = null)
    {
        // Kullanıcı ID'si yoksa mevcut kullanıcıyı kullan
        $targetUserId = $userId ?: Auth::id();
        
        // Yetki kontrolü - başka kullanıcının istatistiklerini görme
        if ($targetUserId != Auth::id()) {
            // Admin veya moderatör mü kontrol et
            if (!Auth::user()->hasAnyRole(['admin', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        }
        
        $user = User::findOrFail($targetUserId);
        $period = $request->input('period', 'month');
        
        $stats = $this->analyticsService->getUserStreamStats($user, $period);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Yayın analizini getirir
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStreamAnalytics($streamId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        
        // Yetki kontrolü - sadece yayıncı veya admin/moderatör görebilir
        $user = Auth::user();
        
        if ($user->id !== $stream->user_id && !$user->hasAnyRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $analytics = $this->analyticsService->getStreamAnalytics($stream);

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Kullanıcının performans özetini getirir
     *
     * @param int|null $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformanceSummary($userId = null)
    {
        // Kullanıcı ID'si yoksa mevcut kullanıcıyı kullan
        $targetUserId = $userId ?: Auth::id();
        
        // Yetki kontrolü - başka kullanıcının özet verilerini görme
        if ($targetUserId != Auth::id()) {
            // Admin veya moderatör mü kontrol et
            if (!Auth::user()->hasAnyRole(['admin', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        }
        
        $user = User::findOrFail($targetUserId);
        $summary = $this->analyticsService->getUserPerformanceSummary($user);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
