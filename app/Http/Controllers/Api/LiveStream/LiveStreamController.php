<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Exceptions\InsufficientCoinsException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgoraChannelResource;
use App\Models\Agora\AgoraChannel;
use App\Services\LiveStream\AgoraChannelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LiveStreamController extends Controller
{
    /**
     * @var AgoraChannelService
     */
    protected $channelService;

    /**
     * LiveStreamController constructor.
     *
     * @param AgoraChannelService $channelService
     */
    public function __construct(AgoraChannelService $channelService)
    {
        $this->channelService = $channelService;
    }

    /**
     * Aktif yayınları listeler
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = [
            'category_id' => $request->input('category_id'),
            'language' => $request->input('language'),
            'featured' => $request->boolean('featured', false),
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'viewer_count'),
            'order_dir' => $request->input('order_dir', 'desc')
        ];

        // Eğer takip edilen kullanıcılar isteniyorsa
        if ($request->boolean('following', false) && Auth::check()) {
            $filters['following_user_id'] = Auth::id();
        }

        $streams = $this->channelService->getActiveStreams($filters);

        return response()->json([
            'success' => true,
            'data' => AgoraChannelResource::collection($streams)
        ]);
    }

    /**
     * Belirli bir kullanıcının yayınlarını listeler
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userStreams(Request $request, $userId)
    {
        $limit = $request->input('limit', 10);
        $streams = $this->channelService->getUserStreams($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => AgoraChannelResource::collection($streams)
        ]);
    }

    /**
     * Yayın detayını getirir
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Stream'i bul - id, channel_name veya stream_key ile
        $stream = AgoraChannel::where('_id', $id)
            ->orWhere('id', $id)
            ->orWhere('channel_name', $id)
            ->orWhere('stream_key', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelResource($stream)
        ]);
    }

    /**
     * Yeni bir yayın başlatır
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:live_stream_categories,id',
            'language' => 'nullable|string|max:10',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $stream = $this->channelService->startStream($user, $request->all());

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start stream'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelResource($stream)
        ], 201);
    }

    /**
     * Yayını aktif duruma geçirir
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function goLive($id)
    {
        $stream = AgoraChannel::findOrFail($id);

        // Yetki kontrolü
        if (Auth::id() !== $stream->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $success = $this->channelService->goLive($stream);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to go live'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelResource($stream)
        ]);
    }

    /**
     * Yayın bilgilerini günceller
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:live_stream_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'thumbnail_url' => 'nullable|string|max:500',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $stream = AgoraChannel::findOrFail($id);

        // Yetki kontrolü
        if (Auth::id() !== $stream->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $success = $this->channelService->updateStream($stream, $request->all());

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stream'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelResource($stream)
        ]);
    }

    /**
     * Yayını sonlandırır
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $stream = AgoraChannel::findOrFail($id);

        // Yetki kontrolü
        if (Auth::id() !== $stream->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $success = $this->channelService->endStream($stream);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to end stream'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelResource($stream)
        ]);
    }

    /**
     * Yayına izleyici olarak katılır
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function join(Request $request, $id)
    {
        // Stream'i bul - id, channel_name veya stream_key ile
        $stream = AgoraChannel::where('_id', $id)
            ->orWhere('id', $id)
            ->orWhere('channel_name', $id)
            ->orWhere('stream_key', $id)
            ->firstOrFail();

        $user = Auth::user();

        // Yayın aktif mi kontrol et
        // ✅ FIX: Use status_id instead of status (which is a string accessor)
        if ($stream->status_id !== AgoraChannel::STATUS_LIVE || !$stream->is_online) {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not active'
            ], 400);
        }

        $deviceInfo = $request->input('device_info', []);
        $viewer = $this->channelService->addViewer($stream, $user, $deviceInfo);

        if (!$viewer) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to join stream'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stream' => new AgoraChannelResource($stream),
                'viewer_id' => $viewer->_id
            ]
        ]);
    }

    /**
     * Yayından ayrılır
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave($id)
    {
        $stream = AgoraChannel::findOrFail($id);
        $user = Auth::user();

        $success = $this->channelService->removeViewer($stream, $user);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to leave stream'
            ], 500);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Yayını beğenir
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function like($id)
    {
        $stream = AgoraChannel::findOrFail($id);
        $user = Auth::user();

        $success = $this->channelService->likeStream($stream, $user);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to like stream'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'likes' => $stream->total_likes
            ]
        ]);
    }

    /**
     * Get all participants in a live stream room
     * Includes host and all cohosts
     *
     * @param string $roomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoomParticipants($roomId)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Fetching room participants', ['room_id' => $roomId]);

            // multi_streams tablosundan aktif katılımcıları çek
            $participants = \Illuminate\Support\Facades\DB::table('multi_streams')
                ->where('room_id', $roomId)
                ->where('is_active', true)
                ->join('users', 'multi_streams.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.username',
                    'users.avatar',
                    'users.level',
                    'multi_streams.stream_id',
                    'multi_streams.stream_type', // 'host' or 'cohost'
                    'multi_streams.joined_at'
                )
                ->orderByRaw("CASE WHEN multi_streams.stream_type = 'host' THEN 0 ELSE 1 END") // Host ilk sırada
                ->orderBy('multi_streams.joined_at', 'asc')
                ->get();

            // Check if there's a mixed stream for this room
            $mixedStream = \Illuminate\Support\Facades\DB::table('mixed_streams')
                ->where('room_id', $roomId)
                ->where('is_active', true)
                ->select('mixed_stream_id', 'task_id')
                ->first();

            \Illuminate\Support\Facades\Log::info('Room participants found', [
                'room_id' => $roomId,
                'participant_count' => $participants->count(),
                'host_count' => $participants->where('stream_type', 'host')->count(),
                'cohost_count' => $participants->where('stream_type', 'cohost')->count(),
                'has_mixed_stream' => $mixedStream !== null,
                'mixed_stream_id' => $mixedStream->mixed_stream_id ?? null
            ]);

            return response()->json([
                'success' => true,
                'participants' => $participants,
                'mixed_stream' => $mixedStream ? [
                    'mixed_stream_id' => $mixedStream->mixed_stream_id,
                    'task_id' => $mixedStream->task_id,
                    'should_use_mixed' => $participants->where('stream_type', 'cohost')->count() > 0
                ] : null,
                'meta' => [
                    'total_count' => $participants->count(),
                    'host_count' => $participants->where('stream_type', 'host')->count(),
                    'cohost_count' => $participants->where('stream_type', 'cohost')->count(),
                    'has_mixed_stream' => $mixedStream !== null
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching room participants', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch room participants',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yayını sonlandırır
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function endStream($id)
    {
        try {
            $stream = AgoraChannel::findOrFail($id);

            // Yetki kontrolü
            if (Auth::id() !== $stream->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Stream'i sonlandır
            $success = $this->channelService->endStream($stream);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to end stream'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stream ended successfully',
                'data' => new AgoraChannelResource($stream->fresh())
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error ending stream', [
                'stream_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to end stream',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
