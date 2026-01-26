<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Exceptions\InsufficientCoinsException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgoraChannelGiftResource;
use App\Models\Agora\AgoraChannel;
use App\Models\Gift;
use App\Services\LiveStream\LiveStreamGiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LiveStreamGiftController extends Controller
{
    /**
     * @var LiveStreamGiftService
     */
    protected $giftService;

    /**
     * LiveStreamGiftController constructor.
     *
     * @param LiveStreamGiftService $giftService
     */
    public function __construct(LiveStreamGiftService $giftService)
    {
        $this->giftService = $giftService;
    }

    /**
     * Yayına gönderilen hediyeleri listeler
     *
     * @param Request $request
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $streamId)
    {
        $limit = $request->input('limit', 50);
        $offset = $request->input('offset', 0);

        // Stream'i bul - id, channel_name veya stream_key ile
        $stream = AgoraChannel::where('_id', $streamId)
            ->orWhere('id', $streamId)
            ->orWhere('channel_name', $streamId)
            ->orWhere('stream_key', $streamId)
            ->firstOrFail();
        $gifts = $this->giftService->getStreamGifts($stream->id, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => AgoraChannelGiftResource::collection($gifts)
        ]);
    }

    /**
     * Bir yayına hediye gönderir
     *
     * @param Request $request
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'gift_id' => 'required|exists:gifts,id',
            'quantity' => 'required|integer|min:1|max:100',
            'message' => 'nullable|string|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Stream'i bul - id, channel_name veya stream_key ile
        $stream = AgoraChannel::where('_id', $streamId)
            ->orWhere('id', $streamId)
            ->orWhere('channel_name', $streamId)
            ->orWhere('stream_key', $streamId)
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
        
        // Hediye gönderme yetkisi var mı?
        if (!$this->giftService->canSendGifts($stream, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send gifts to this stream'
            ], 403);
        }

        try {
            $gift = $this->giftService->sendGift(
                $stream,
                $user,
                $request->input('gift_id'),
                $request->input('quantity', 1),
                $request->input('message')
            );

            return response()->json([
                'success' => true,
                'data' => new AgoraChannelGiftResource($gift)
            ], 201);
        } catch (InsufficientCoinsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient coins',
                'code' => 'INSUFFICIENT_COINS'
            ], 402);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send gift: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yayındaki en yüksek bağışçıları listeler
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function topDonators($streamId)
    {
        // Stream'i bul - id, channel_name veya stream_key ile
        $stream = AgoraChannel::where('_id', $streamId)
            ->orWhere('id', $streamId)
            ->orWhere('channel_name', $streamId)
            ->orWhere('stream_key', $streamId)
            ->firstOrFail();
        $donators = $this->giftService->getTopDonators($stream->id);

        return response()->json([
            'success' => true,
            'data' => $donators
        ]);
    }

    /**
     * Mevcut kullanıcının gönderdiği hediyeleri listeler
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myGifts(Request $request)
    {
        $limit = $request->input('limit', 50);
        $offset = $request->input('offset', 0);
        
        $user = Auth::user();
        $gifts = $this->giftService->getUserGifts($user->id, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => AgoraChannelGiftResource::collection($gifts)
        ]);
    }

    /**
     * Kullanılabilir hediyeleri listeler
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableGifts()
    {
        $gifts = Gift::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gifts
        ]);
    }
}
