<?php

namespace App\GraphQL\Resolvers;

use Exception;
use Carbon\Carbon;
use \FFMpeg\FFMpeg;
use App\Models\User;
use App\Models\Follow;
use App\Events\UserTyping;
use App\Events\MessageRead;
use App\Events\MessageSent;
use Illuminate\Support\Str;
use App\Models\Chat\Message;
use App\Events\MessageDeleted;
use App\Models\Chat\Conversation;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Notifications\ConversationUpdatedNotification;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Notifications\MessageSentToFollowersNotification;

class ChatResolver
{
    /**
     * Get conversations for the authenticated user
     */
    public function getConversations($rootValue, array $args)
    {
        try {
            $user = Auth::user();
            $page = $args['page'] ?? 1;
            $perPage = $args['per_page'] ?? 15;

            $conversations = Conversation::where('participants', 'all', [$user->id])
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $total = Conversation::where('participants', 'all', [$user->id])
                ->where('is_active', true)
                ->count();

            // Enhance conversations with additional data
            $enhancedConversations = $conversations->map(function ($conversation) use ($user) {
                // Get the other participant
                $otherParticipantId = collect($conversation->getParticipantIds())->first(function ($participantId) use ($user) {
                    return $participantId != $user->id;
                });

                $otherUser = User::find($otherParticipantId);

                return [
                    'id' => (string) $conversation->_id,
                    'participants' => $conversation->getParticipantIds(),
                    'last_message' => $conversation->last_message,
                    'unread_count' => $conversation->getUnreadCountForUser($user->id),
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                    'other_user' => $otherUser ? [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'surname' => $otherUser->surname,
                        'nickname' => $otherUser->nickname,
                        'avatar' => $otherUser->avatar
                    ] : null
                ];
            });

            return [
                'conversations' => $enhancedConversations,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage
            ];
        } catch (\Throwable $e) {
            return [
                'conversations' => [],
                'page' => 1,
                'per_page' => 15,
                'total' => 0
            ];
        }
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages($rootValue, array $args)
    {
        $user = Auth::user();
        $conversationId = $args['conversation_id'];
        $page = $args['page'] ?? 1;
        $perPage = $args['per_page'] ?? 20;

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return [
                'messages' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage
            ];
        }

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            return [
                'messages' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage
            ];
        }

        // Mark conversation as read first
        $conversation->markAllAsRead($user->id);

        // Get messages for this user (excluding deleted ones) after marking as read
        $messages = $conversation->getMessagesForUser($user->id, $perPage, $page);

        // Count total messages
        $total = Message::where('conversation_id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->whereNull('deleted_by')
                    ->orWhere(function ($q) use ($user) {
                        $q->whereNotIn('deleted_by', [$user->id]);
                    });
            })
            ->count();

        // Enhance messages with sender information
        $enhancedMessages = $messages->map(function ($message) {
            $sender = $message->sender();

            $data = [
                'id' => (string) $message->_id,
                'conversation_id' => (string) $message->conversation_id,
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'type' => $message->type ?? 'text',
                'media_url' => $message->media_url,
                'thumbnail_url' => $message->thumbnail_url,
                'duration' => $message->duration,
                'is_read' => $message->is_read,
                'read_at' => $message->read_at,
                'reply_to' => $message->reply_to,
                'reactions' => $message->reactions,
                'created_at' => $message->created_at,
                'sender' => $sender ? [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'surname' => $sender->surname,
                    'nickname' => $sender->nickname,
                    'avatar' => $sender->avatar
                ] : null,
                'shared_video' => null,
                'shared_profile' => null,
            ];

            if ($message->type == 'shared_video') {
                $video = Video::find($message->shared_video_id);
                if ($video) {
                    $data['shared_video'] = [
                        'id' => $message->shared_video_id,
                        'thumbnail_url' => $video->thumbnail_url,
                        'video_url' => $video->video_url,
                        'description' => $video->description,
                    ];
                }
            } else if ($message->type == 'shared_profile') {
                $user = User::find($message->shared_profile_id);
                if ($user) {
                    $data['shared_profile'] = [
                        'id' => $message->shared_profile_id,
                        'avatar' => $user->avatar,
                        'name' => $user->name,
                        'surname' => $user->surname,
                        'nickname' => $user->nickname,
                    ];
                }
            }

            return $data;
        });

        return [
            'messages' => $enhancedMessages,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Start or get a conversation with another user
     */
    public function startConversation($rootValue, array $args)
    {
        $user = Auth::user();
        $receiverId = $args['receiver_id'];

        // Validate receiver exists
        $receiver = User::find($receiverId);
        if (!$receiver) {
            throw new Exception('Receiver not found');
        }

        // Find or create conversation
        $conversation = Conversation::findOrCreateConversation($user->id, $receiverId);

        // Get other participant info
        $otherUser = $user->id === $receiverId ? $user : $receiver;

        return [
            'id' => (string) $conversation->_id,
            'participants' => $conversation->getParticipantIds(),
            'last_message' => $conversation->last_message,
            'unread_count' => $conversation->getUnreadCountForUser($user->id),
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'surname' => $otherUser->surname,
                'nickname' => $otherUser->nickname,
                'avatar' => $otherUser->avatar
            ]
        ];
    }

    /**
     * Send a new text message
     */
    public function sendMessage($rootValue, array $args)
    {
        $authUser = Auth::user();
        $conversationId = $args['input']['conversation_id'];
        $content = $args['input']['content'];
        $replyTo = $args['input']['reply_to'] ?? null;

        /** @var Conversation $conversation */
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        if (!in_array($authUser->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Mesajı oluştur
        $message = $conversation->addMessage([
            'sender_id' => $authUser->id,
            'content' => $content,
            'type' => 'text',
            'reply_to' => $replyTo
        ]);

        // MessageSent olayını tetikle
        event(new MessageSent($message));

        //mesajı gonderen user hariç diğer userlara bildirim gönder
        $userIdsForNotify = array_filter($conversation->getParticipantIds(), fn($id) => $id !== $authUser->id);
        foreach ($userIdsForNotify as $participantId) {
            $user = User::find($participantId);
            if ($user) {
                $user->notify(new ConversationUpdatedNotification($conversation, $message));
            }
        }

        $sender = $message->sender();

        return [
            'id' => (string) $message->_id,
            'conversation_id' => (string) $message->conversation_id,
            'sender_id' => $message->sender_id,
            'content' => $message->content,
            'type' => $message->type,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at,
            'reply_to' => $message->reply_to,
            'reactions' => $message->reactions,
            'created_at' => $message->created_at,
            'has_banned_word' => (bool) $message->has_banned_word,
            'sender' => $sender ? [
                'id' => $sender->id,
                'name' => $sender->name,
                'surname' => $sender->surname,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar
            ] : null,
            'mentions' => $message->mentions,
        ];
    }

    /**
     * Send a media message (image, video, audio, gif)
     */
    private const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB limit
    private const ALLOWED_TYPES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'video' => ['mp4', 'mov', 'avi'],
        'audio' => ['mp3', 'wav', 'ogg'],
        'gif' => ['gif']
    ];

    public function sendMediaMessage($rootValue, array $args)
    {
        $user = Auth::user();
        $input = $args['input'];
        $conversationId = $input['conversation_id'];
        $base64File = $input['media_content'];
        $type = $input['type']; // image, video, audio, gif
        $replyTo = $input['reply_to'] ?? null;

        // Validate media type
        if (!array_key_exists($type, self::ALLOWED_TYPES)) {
            throw new Exception('Invalid media type');
        }

        /** @var Conversation $conversation */
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Validate and process base64 file
        if (!preg_match('/^data:([\w\/]+);base64,/', $base64File, $matches)) {
            throw new Exception('Invalid base64 format');
        }

        $mimeType = $matches[1];
        $extension = $this->getExtensionByType($type);

        if (!in_array($extension, self::ALLOWED_TYPES[$type])) {
            throw new Exception('Invalid file extension for type: ' . $type);
        }

        // Process base64 file
        $fileData = base64_decode(preg_replace('#^data:' . $mimeType . ';base64,#i', '', $base64File));

        if ($fileData === false) {
            throw new Exception('Invalid base64 content');
        }

        // Check file size
        $fileSize = strlen($fileData);
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new Exception('File size exceeds limit of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }

        // Generate unique filename
        $fileName = Str::uuid() . '.' . $extension;
        $path = 'chat/' . $conversationId . '/' . $fileName;

        // Store file in private disk
        Storage::disk('private')->put($path, $fileData);
        $mediaUrl = Storage::disk('private')->url($path);

        // Generate thumbnail and get duration for video/gif
        $thumbnailUrl = null;
        $duration = null;

        if ($type === 'video') {
            try {
                $ffprobe = \FFMpeg\FFProbe::create();
                $duration = $ffprobe
                    ->format(Storage::disk('private')->path($path))
                    ->get('duration');

                // Generate thumbnail
                $video = \FFMpeg\FFMpeg::create()
                    ->open(Storage::disk('private')->path($path));

                $thumbnailPath = 'chat/' . $conversationId . '/thumbnails/' . Str::uuid() . '.jpg';
                $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
                $frame->save(Storage::disk('private')->path($thumbnailPath));

                $thumbnailUrl = Storage::disk('private')->url($thumbnailPath);
            } catch (Exception $e) {
                Log::error('Failed to process video: ' . $e->getMessage());
                // Continue without thumbnail/duration if processing fails
            }
        } elseif ($type === 'gif') {
            // For GIFs, we'll just use the original file as preview
            $thumbnailUrl = $mediaUrl;
        }

        // Create message
        $message = $conversation->addMessage([
            'sender_id' => $user->id,
            'content' => '',
            'type' => $type,
            'media_url' => $mediaUrl,
            'thumbnail_url' => $thumbnailUrl,
            'duration' => $duration,
            'reply_to' => $replyTo
        ]);

        // Broadcast message via Reverb WebSockets
        event(new MessageSent($message));

        // Also broadcast conversation update to update conversations list
        $userIdsForNotify = array_filter($conversation->getParticipantIds(), fn($id) => $id !== $user->id);
        foreach ($userIdsForNotify as $participantId) {
            $user = User::find($participantId);
            if ($user) {
                $user->notify(new ConversationUpdatedNotification($conversation, $message));
            }
        }

        $sender = $message->sender();

        return [
            'id' => (string) $message->_id,
            'conversation_id' => (string) $message->conversation_id,
            'sender_id' => $message->sender_id,
            'content' => $message->content,
            'type' => $message->type,
            'media_url' => $message->media_url,
            'thumbnail_url' => $message->thumbnail_url,
            'duration' => $message->duration,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at,
            'reply_to' => $message->reply_to,
            'reactions' => $message->reactions,
            'created_at' => $message->created_at,
            'sender' => $sender ? [
                'id' => $sender->id,
                'name' => $sender->name,
                'surname' => $sender->surname,
                'nickname' => $sender->nickname,
                'avatar' => $sender->avatar
            ] : null
        ];
    }

    /**
     * Mark a message as read
     */
    public function markMessageAsRead($rootValue, array $args): array
    {
        $user = Auth::user();
        $messageId = $args['message_id'];

        $message = Message::find($messageId);

        if (!$message) {
            throw new Exception('Message not found');
        }

        $conversation = Conversation::find($message->conversation_id);

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Only mark as read if the user is not the sender
        if ($message->sender_id != $user->id && !$message->is_read) {
            $message->markAsRead();

            // Broadcast read event via Reverb WebSockets
            event(new MessageRead(
                $message->conversation_id,
                $user->id,
                (string) $message->_id,
                $message->read_at->toDateTimeString()
            ));
        }

        return [
            'success' => true,
            'message' => 'Mesaj okundu.'
        ];
    }

    /**
     * Mark all messages in a conversation as read
     */
    public function markConversationAsRead($rootValue, array $args): array
    {
        $user = Auth::user();
        $conversationId = $args['conversation_id'];

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Mark all messages as read
        $unreadMessages = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->get();

        // Mark all as read and broadcast events for each
        foreach ($unreadMessages as $message) {
            $message->markAsRead();

            // Broadcast read event via Reverb WebSockets
            event(new MessageRead(
                $message->conversation_id,
                $user->id,
                (string) $message->_id,
                $message->read_at->toDateTimeString()
            ));
        }

        // Also call the original method to ensure all are marked as read
        $conversation->markAllAsRead($user->id);

        return [
            'success' => true,
            'message' => 'Tüm mesajlar okundu.'
        ];
    }

    /**
     * Add a reaction to a message
     */
    public function addReaction($rootValue, array $args)
    {
        $user = Auth::user();
        $messageId = $args['message_id'];
        $reaction = $args['reaction'];

        $message = Message::find($messageId);

        if (!$message) {
            throw new Exception('Mesaj bulunamadı.');
        }

        $conversation = Conversation::find($message->conversation_id);

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Add reaction
        $message->addReaction($user->id, $reaction);

        // Broadcast message update via Reverb WebSockets
        event(new MessageSent($message));

        return [
            'success' => true,
            'message' => 'Reaction added'
        ];
    }

    /**
     * Remove a reaction from a message
     */
    public function removeReaction($rootValue, array $args)
    {
        $user = Auth::user();
        $messageId = $args['message_id'];

        $message = Message::find($messageId);

        if (!$message) {
            throw new Exception('Mesaj bulunamadı.');
        }

        $conversation = Conversation::find($message->conversation_id);

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Remove reaction
        $message->removeReaction($user->id);

        // Broadcast message update via Reverb WebSockets
        event(new MessageSent($message));

        return [
            'success' => true,
            'message' => 'Reaction removed'
        ];
    }

    /**
     * Delete a message (soft delete)
     */
    public function deleteMessage($rootValue, array $args)
    {
        $user = Auth::user();
        $messageId = $args['message_id'];

        $message = Message::find($messageId);

        if (!$message) {
            throw new Exception('Mesaj bulunamadı.');
        }

        // Check if user is the sender or if message is not read yet
        if ($message->sender_id != $user->id && $message->is_read) {
            throw new Exception('You can only delete your own messages or unread messages');
        }

        // Soft delete message
        $message->softDelete($user->id);

        // Broadcast message update via Reverb WebSockets
        event(new MessageDeleted($message));

        return [
            'success' => true,
            'message' => 'Mesaj silindi.'
        ];
    }

    /**
     * Set typing status
     */
    public function setTypingStatus($rootValue, array $args)
    {
        $user = Auth::user();
        $conversationId = $args['conversation_id'];
        $isTyping = $args['is_typing'];

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        // Check if user is part of this conversation
        if (!in_array($user->id, $conversation->getParticipantIds())) {
            throw new Exception('You are not part of this conversation');
        }

        // Broadcast typing event via Reverb WebSockets
        event(new UserTyping($conversationId, $user->id, $isTyping));

        return [
            'success' => true,
            'message' => 'Typing status updated'
        ];
    }


    /**
     * Helper function to get file extension by type
     */
    private function getExtensionByType($type)
    {
        switch ($type) {
            case 'image':
                return 'jpg';
            case 'video':
                return 'mp4';
            case 'audio':
                return 'mp3';
            case 'gif':
                return 'gif';
            default:
                return 'bin';
        }
    }

    public function messageSentToFollowers($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $authUser = $context->user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'message' => 'required|string|max:255',
        ], [
            'message.required' => 'Mesaj gereklidir.',
            'message.string' => 'Mesaj metin olmalıdır.',
            'message.max' => 'Mesaj maksimum 255 karakter olmalıdır.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        /*$key = "user:{$authUser->id}:message_sent_to_followers_notification_lock";

        if (Cache::has($key)) {
            return [
                'success' => false,
                'message' => "Saatte yalnızca bir toplu mesaj gönderebilirsiniz.",
            ];
        }*/

        $notification = new MessageSentToFollowersNotification($authUser, $input['message']);

        Follow::where('followed_id', $authUser->id)
            ->with('follower')
            ->chunk(1000, function ($follows) use ($notification) {
                foreach ($follows as $follow) {
                    $follower = $follow->follower;
                    if ($follower) {
                        $follower->notify($notification);
                    }
                }
            });

        //Cache::put($key, now(), now()->addHour());

        return [
            'success' => true,
            'message' => 'Gönderildi',
        ];
    }

    /**
     * Search conversations by user name, surname, and avatar (no message search)
     */
    public function searchConversations($rootValue, array $args)
    {
        $user = Auth::user();
        $query = $args['query'] ?? '';

        if (empty($query)) {
            return [
                'conversations' => [],
            ];
        }

        // Get conversations where the authenticated user is a participant
        $conversations = Conversation::where('participants', 'all', [$user->id])
            ->where('is_active', true)
            ->get()
            ->filter(function ($conversation) use ($user, $query) {
                // Get the other participant
                $otherParticipantId = collect($conversation->participants)
                    ->first(function ($participantId) use ($user) {
                    return $participantId != $user->id;
                });

                $otherParticipant = User::find($otherParticipantId);

                // Check if the other participant's name, surname or nickname contains the query
                if (
                    $otherParticipant &&
                    (stripos($otherParticipant->name, $query) !== false ||
                        stripos($otherParticipant->surname, $query) !== false ||
                        stripos($otherParticipant->nickname, $query) !== false)
                ) {
                    return true;
                }

                return false;
            });

        // Empty messages array - we're not searching messages anymore
        $messages = collect([]);

        // Enhance conversations with additional data
        $enhancedConversations = $conversations->map(function ($conversation) use ($user) {
            // Get the other participant
            $otherParticipantId = collect($conversation->participants)
                ->first(function ($participantId) use ($user) {
                    return $participantId != $user->id;
                });

            $otherParticipant = User::find($otherParticipantId);

            // Get the last message
            $lastMessage = Message::where('conversation_id', $conversation->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Count unread messages
            $unreadCount = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->where(function ($query) {
                    $query->whereNull('read_at')
                        ->orWhere('read_at', '0000-00-00 00:00:00');
                })
                ->count();

            return [
                'id' => $conversation->id,
                'participants' => $conversation->participants,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
                'other_user' => $otherParticipant ? [
                    'id' => $otherParticipant->id,
                    'name' => $otherParticipant->name,
                    'nickname' => $otherParticipant->username,
                    'avatar' => $otherParticipant->avatar
                ] : null,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'content' => $lastMessage->content,
                    'type' => $lastMessage->type,
                    'created_at' => $lastMessage->created_at,
                    'sender_id' => $lastMessage->sender_id
                ] : null,
                'unread_count' => $unreadCount
            ];
        })->values()->all();

        // Enhance messages with additional data
        $enhancedMessages = $messages->map(function ($message) {
            $sender = User::find($message->sender_id);

            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'type' => $message->type,
                'media_url' => $message->file_url,
                'created_at' => $message->created_at,
                'read_at' => $message->read_at,
                'is_read' => !empty($message->read_at),
                'sender' => $sender ? [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'nickname' => $sender->username,
                    'avatar' => $sender->avatar
                ] : null
            ];
        })->values()->all();

        return [
            'conversations' => $enhancedConversations,
            'messages' => $enhancedMessages
        ];
    }


    public function video($rootValue, array $args)
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'user_ids' => 'required|array|max:5',
            'video_id' => 'required|string',
            'content' => 'nullable|string|max:255',
        ], [
            'user_ids.required' => 'Kullanıcı IDleri gereklidir.',
            'user_ids.array' => 'Kullanıcı IDleri dizi olmalıdır.',
            'user_ids.max' => 'En fazla 5 kullanıcı seçilebilir.',
            'video_id.required' => 'Video ID gereklidir.',
            'video_id.string' => 'Video ID metin olmalıdır.',
            'content.string' => 'Mesaj metin olmalıdır.',
            'content.max' => 'Mesaj maksimum 255 karakter olmalıdır.',
        ]);

        $video = Video::find($input['video_id']);
        if (!$video) {
            throw ValidationException::withMessages([
                'video_id' => 'Video bulunamadı.',
            ]);
        }

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $userIds = $input['user_ids'];
        $videoId = $input['video_id'];
        $content = $input['content'];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user)
                continue;

            $conversation = Conversation::findOrCreateConversation($authUser->id, $user->id);
            if ($conversation && !in_array($userId, $conversation->getParticipantIds())) {
                continue;
            }

            $message = $conversation->addMessage([
                'sender_id' => $authUser->id,
                'content' => $content,
                'type' => 'shared_video',
                'shared_video_id' => $videoId,
            ]);

            event(new MessageSent($message));

            //mesajı gonderen user hariç diğer userlara bildirim gönder
            $userIdsForNotify = array_filter($conversation->getParticipantIds(), fn($id) => $id !== $authUser->id);
            foreach ($userIdsForNotify as $participantId) {
                $user = User::find($participantId);
                if ($user) {
                    $user->notify(new ConversationUpdatedNotification($conversation, $message));
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Video paylaşıldı.',
        ];
    }

    public function profile($rootValue, array $args)
    {
        $authUser = Auth::user();
        $input = $args['input'];

        $validator = Validator::make($input, [
            'user_ids' => 'required|array|max:5',
            'shared_user_id' => 'required|string',
            'content' => 'nullable|string|max:255',
        ], [
            'user_ids.required' => 'Kullanıcı IDleri gereklidir.',
            'user_ids.array' => 'Kullanıcı IDleri dizi olmalıdır.',
            'user_ids.max' => 'En fazla 5 kullanıcı seçilebilir.',
            'shared_user_id.required' => 'Paylaşılan Kullanıcı ID gereklidir.',
            'shared_user_id.string' => 'Paylaşılan Kullanıcı ID metin olmalıdır.',
            'content.string' => 'Mesaj metin olmalıdır.',
            'content.max' => 'Mesaj maksimum 255 karakter olmalıdır.',
        ]);

        $user = User::find($input['shared_user_id']);
        if (!$user) {
            throw ValidationException::withMessages([
                'shared_user_id' => 'Paylaşılan Kullanıcı bulunamadı.',
            ]);
        }

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $userIds = $input['user_ids'];
        $sharedUserId = $input['shared_user_id'];
        $content = $input['content'];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user)
                continue;

            $conversation = Conversation::findOrCreateConversation($authUser->id, $user->id);
            if ($conversation && !in_array($userId, $conversation->getParticipantIds())) {
                continue;
            }

            $message = $conversation->addMessage([
                'sender_id' => $authUser->id,
                'content' => $content,
                'type' => 'shared_profile',
                'shared_profile_id' => $sharedUserId,
            ]);

            event(new MessageSent($message));

            //mesajı gonderen user hariç diğer userlara bildirim gönder
            $userIdsForNotify = array_filter($conversation->getParticipantIds(), fn($id) => $id !== $authUser->id);
            foreach ($userIdsForNotify as $participantId) {
                $user = User::find($participantId);
                if ($user) {
                    $user->notify(new ConversationUpdatedNotification($conversation, $message));
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Profil paylaşıldı.',
        ];
    }
}
