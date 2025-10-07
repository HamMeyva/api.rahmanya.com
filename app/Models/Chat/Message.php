<?php

namespace App\Models\Chat;

use Carbon\Carbon;
use App\Models\User;
use App\Casts\DatetimeTz;
use App\Models\Traits\MongoTimestamps;
use App\Models\Chat\Conversation;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\MessageObserver;

/**
 * Message model for storing messages in MongoDB
 *
 * @mixin IdeHelperMessage
 */
#[ObservedBy(MessageObserver::class)]
class Message extends Model
{
    use MongoTimestamps;

    protected $connection = 'mongodb';
    protected $collection = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'original_content', // yasaklı kelime var ise sansürsüz hali burada
        'has_banned_word', // yasaklı kelime var mı
        'type',
        'media_url',
        'thumbnail_url',
        'duration',
        'is_read',
        'read_at',
        'reply_to',
        'reactions',
        'deleted_by',
        'is_typing',
        'mentions',
        'shared_video_id',
        'shared_profile_id',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => DatetimeTz::class,
            'is_typing' => 'boolean',
            'has_banned_word' => 'boolean',
        ];
    }

    public function index()
    {
        return [
            [
                'keys' => ['conversation_id' => 1, 'created_at' => -1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['sender_id' => 1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['is_read' => 1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['type' => 1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['reply_to' => 1],
                'options' => ['background' => true]
            ],
            [
                'keys' => ['content' => 'text'],
                'options' => ['background' => true, 'default_language' => 'turkish']
            ]
        ];
    }

    public function sender()
    {
        return User::find($this->sender_id);
    }

    public function conversation()
    {
        return Conversation::find($this->conversation_id);
    }

    public function repliedMessage()
    {
        if ($this->reply_to) {
            return self::find($this->reply_to);
        }
        return null;
    }

    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = Carbon::now();
        $this->save();

        return $this;
    }

    public function addReaction($userId, $reaction)
    {
        $reactions = $this->reactions ?? [];

        // Check if user already reacted
        $existingReactionIndex = array_search($userId, array_column($reactions, 'user_id'));

        if ($existingReactionIndex !== false) {
            // Update existing reaction
            $reactions[$existingReactionIndex] = [
                'user_id' => $userId,
                'reaction' => $reaction,
                'created_at' => Carbon::now()
            ];
        } else {
            // Add new reaction
            $reactions[] = [
                'user_id' => $userId,
                'reaction' => $reaction,
                'created_at' => Carbon::now()
            ];
        }

        $this->reactions = $reactions;
        $this->save();

        return $this;
    }

    public function removeReaction($userId)
    {
        $reactions = $this->reactions ?? [];

        // Filter out the reaction from the user
        $filteredReactions = array_filter($reactions, function ($reaction) use ($userId) {
            return $reaction['user_id'] != $userId;
        });

        $this->reactions = array_values($filteredReactions);
        $this->save();

        return $this;
    }

    public function softDelete($userId)
    {
        // Only allow deletion if message is not read or if sender is deleting
        if (!$this->is_read || $this->sender_id == $userId) {
            $deletedBy = $this->deleted_by ?? [];

            if (!in_array($userId, $deletedBy)) {
                $deletedBy[] = $userId;
                $this->deleted_by = $deletedBy;

                // If sender deletes, mark deleted_at
                if ($this->sender_id == $userId) {
                    $this->deleted_at = Carbon::now();
                }

                $this->save();
            }
        }

        return $this;
    }

    public function setTypingStatus($isTyping)
    {
        $this->is_typing = $isTyping;
        $this->save();

        return $this;
    }
}
