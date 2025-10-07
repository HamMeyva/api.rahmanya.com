<?php

namespace App\Http\Controllers\Api\LiveStream;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgoraChannelMessageResource;
use App\Models\Agora\AgoraChannel;
use App\Models\Agora\AgoraChannelMessage;
use App\Services\LiveStream\LiveStreamChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LiveStreamChatController extends Controller
{
    /**
     * @var LiveStreamChatService
     */
    protected $chatService;

    /**
     * LiveStreamChatController constructor.
     *
     * @param LiveStreamChatService $chatService
     */
    public function __construct(LiveStreamChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Yayın mesajlarını getirir
     *
     * @param Request $request
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $streamId)
    {
        $limit = $request->input('limit', 50);
        $lastMessageId = $request->input('last_message_id');
        
        $stream = AgoraChannel::findOrFail($streamId);
        $messages = $this->chatService->getStreamMessages($stream->id, $limit, $lastMessageId);

        return response()->json([
            'success' => true,
            'data' => AgoraChannelMessageResource::collection($messages)
        ]);
    }

    /**
     * Mesaj gönderir
     *
     * @param Request $request
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
            'parent_message_id' => 'nullable|string|exists:agora_channel_messages,_id',
            'sticker_id' => 'nullable|integer|exists:stickers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $stream = AgoraChannel::findOrFail($streamId);
        $user = Auth::user();
        
        // Yayın aktif mi kontrol et
        if ($stream->status !== AgoraChannel::STATUS_LIVE || !$stream->is_online) {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not active'
            ], 400);
        }

        $options = [
            'parent_message_id' => $request->input('parent_message_id'),
            'sticker_id' => $request->input('sticker_id')
        ];

        $message = $this->chatService->sendMessage($stream, $user, $request->input('message'), $options);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelMessageResource($message)
        ], 201);
    }

    /**
     * Mesajı pinler
     *
     * @param Request $request
     * @param string $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function pin(Request $request, $messageId)
    {
        $message = AgoraChannelMessage::findOrFail($messageId);
        $user = Auth::user();
        
        // Mesajın ait olduğu yayını bul
        $stream = AgoraChannel::findOrFail($message->agora_channel_id);
        
        // İşlemi gerçekleştir
        $isPinned = $request->boolean('is_pinned', true);
        $success = $this->chatService->pinMessage($message, $user, $isPinned);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pin/unpin message'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelMessageResource($message)
        ]);
    }

    /**
     * Mesajı siler
     *
     * @param string $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($messageId)
    {
        $message = AgoraChannelMessage::findOrFail($messageId);
        $user = Auth::user();
        
        // İşlemi gerçekleştir
        $success = $this->chatService->deleteMessage($message, $user);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message'
            ], 500);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Kullanıcıyı engeller
     *
     * @param Request $request
     * @param string $streamId
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockUser(Request $request, $streamId, $userId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        $moderator = Auth::user();
        
        // İşlemi gerçekleştir
        $isBlocked = $request->boolean('is_blocked', true);
        $success = $this->chatService->blockUser($stream, $moderator, $userId, $isBlocked);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to block/unblock user'
            ], 500);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Moderatör olarak ata
     *
     * @param Request $request
     * @param string $streamId
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignModerator(Request $request, $streamId, $userId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        $owner = Auth::user();
        
        // Yayın sahibi kontrolü
        if ($owner->id !== $stream->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Only stream owner can assign moderators'
            ], 403);
        }
        
        // İşlemi gerçekleştir
        $isModerator = $request->boolean('is_moderator', true);
        $success = $this->chatService->assignModerator($stream, $owner, $userId, $isModerator);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign/remove moderator'
            ], 500);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Sabitlenmiş mesajı getirir
     *
     * @param string $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPinnedMessage($streamId)
    {
        $stream = AgoraChannel::findOrFail($streamId);
        $pinnedMessage = $this->chatService->getPinnedMessage($stream->id);

        if (!$pinnedMessage) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new AgoraChannelMessageResource($pinnedMessage)
        ]);
    }
}
