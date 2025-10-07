<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use App\Casts\DatetimeTz;
use App\Models\BannedWord;
use App\Models\Traits\MongoTimestamps;

/**
 * Conversation model for storing conversations in MongoDB
 *
 * @mixin IdeHelperConversation
 */
class Conversation extends Model
{
    use MongoTimestamps;
    
    protected $connection = 'mongodb';
    protected $collection = 'conversations';

    protected $fillable = [
        'participants',
        'last_message',
        'unread_count',
        'is_active',
        'metadata'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the indexes for the model.
     *
     * @return array
     */
    public function index()
    {
        return [
            [
                'keys' => ['participants' => 1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['is_active' => 1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['updated_at' => -1],
                'options' => ['background' => true]
            ]
        ];
    }

    /**
     * Custom method to get the participants users
     * This is a workaround for cross-database relationships (MongoDB to SQL)
     */
    public function users()
    {
        $userIds = $this->participants ?? [];
        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Custom method to get the participant IDs
     */
    public function getParticipantIds()
    {
        return $this->participants ?? [];
    }

    /**
     * Custom method to get messages for this conversation
     */
    public function messages()
    {
        return Message::where('conversation_id', $this->_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the latest messages for this conversation with pagination
     */
    public function getLatestMessages($limit = 20, $page = 1)
    {
        return Message::where('conversation_id', $this->_id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->sortBy('created_at');
    }

    /**
     * Get messages for a user, excluding those they've deleted
     */
    public function getMessagesForUser($userId, $limit = 20, $page = 1)
    {
        return Message::where('conversation_id', $this->_id)
            ->where(function ($query) use ($userId) {
                $query->whereNull('deleted_by')
                    ->orWhere(function ($q) use ($userId) {
                        $q->whereNotIn('deleted_by', [$userId]);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->sortBy('created_at');
    }

    /**
     * Add a new message to the conversation
     */
    public function addMessage($data)
    {
        $messageData = [
            'conversation_id' => $this->_id,
            'sender_id' => $data['sender_id'],
            'content' => $data['content'],
            'type' => $data['type'] ?? 'text',
            'media_url' => $data['media_url'] ?? null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'duration' => $data['duration'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'is_read' => false,
        ];

        $hasBannedWord = BannedWord::hasBannedWord($data['content']);
        if ($hasBannedWord) {
            $censoredContent = BannedWord::censor($data['content']);
            $messageData['content'] = $censoredContent;
            $messageData['original_content'] = $data['content'];
            $messageData['has_banned_word'] = true;
        }

        if($data['type'] == 'shared_video') {
            $messageData['shared_video_id'] = $data['shared_video_id'] ?? null;
        }

        if($data['type'] == 'shared_profile') {
            $messageData['shared_profile_id'] = $data['shared_profile_id'] ?? null;
        }

        // Create the message
        $message = Message::create($messageData);

        // Update the conversation's last message and unread counts
        $this->updateLastMessage($message);
        $this->incrementUnreadCount($data['sender_id']);

        return $message;
    }

    /**
     * Update the last message information
     */
    public function updateLastMessage($message)
    {
        $this->last_message = [
            'id' => $message->_id,
            'sender_id' => $message->sender_id,
            'content' => $message->content,
            'type' => $message->type,
            'created_at' => $message->created_at
        ];

        $this->updated_at = Carbon::now();
        $this->save();

        return $this;
    }

    /**
     * Increment unread count for all participants except the sender
     */
    public function incrementUnreadCount($senderId)
    {
        $unreadCount = $this->unread_count ?? [];

        foreach ($this->participants as $participantId) {
            if ($participantId != $senderId) {
                if (!isset($unreadCount[$participantId])) {
                    $unreadCount[$participantId] = 0;
                }
                $unreadCount[$participantId]++;
            }
        }

        $this->unread_count = $unreadCount;
        $this->save();

        return $this;
    }

    /**
     * Reset unread count for a specific user
     */
    public function resetUnreadCount($userId)
    {
        $unreadCount = $this->unread_count ?? [];
        $unreadCount[$userId] = 0;

        $this->unread_count = $unreadCount;
        $this->save();

        return $this;
    }

    /**
     * Get unread count for a specific user
     */
    public function getUnreadCountForUser($userId)
    {
        $unreadCount = $this->unread_count ?? [];
        return $unreadCount[$userId] ?? 0;
    }

    /**
     * Find or create a conversation between two users
     */
    public static function findOrCreateConversation($user1Id, $user2Id)
    {
        // Sort user IDs to ensure consistent querying
        $participants = [$user1Id, $user2Id];
        sort($participants);

        // Try to find an existing conversation
        $conversation = self::where('participants', $participants)
            ->where('is_active', true)
            ->first();

        // Create a new conversation if none exists
        if (!$conversation) {
            $conversation = self::create([
                'participants' => $participants,
                'unread_count' => [],
                'is_active' => true,
                'metadata' => [
                    'created_by' => $user1Id,
                    'created_at' => Carbon::now()->toDateTimeString()
                ]
            ]);
        }

        return $conversation;
    }

    /**
     * Mark all messages as read for a specific user
     */
    public function markAllAsRead($userId)
    {
        // Get all unread messages
        $unreadMessages = Message::where('conversation_id', $this->_id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->get();

        // Update each message individually
        foreach ($unreadMessages as $message) {
            $message->is_read = true;
            $message->read_at = Carbon::now();
            $message->save();

            // Broadcast read event for each message
            event(new \App\Events\MessageRead(
                $message->conversation_id,
                $userId,
                (string)$message->_id,
                $message->read_at->toDateTimeString()
            ));
        }

        // Reset unread count
        $this->resetUnreadCount($userId);

        return $this;
    }
}