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
        $stream = AgoraChannel::findOrFail($id);

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
        $stream = AgoraChannel::findOrFail($id);
        $user = Auth::user();

        // Yayın aktif mi kontrol et
        if ($stream->status !== AgoraChannel::STATUS_LIVE || !$stream->is_online) {
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
}
