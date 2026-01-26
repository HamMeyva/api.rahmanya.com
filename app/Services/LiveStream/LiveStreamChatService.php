<?php

namespace App\Services\LiveStream;

use Exception;
use Carbon\Carbon;
use App\Models\Gift;
use App\Models\User;
use App\Models\Admin;
use App\Models\BannedWord;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use App\Models\Agora\AgoraChannelViewer;
use App\Models\Agora\AgoraChannelMessage;
use App\Events\LiveStream\ModeratorRemoved;
use App\Events\LiveStream\ModeratorAssigned;
use App\Events\LiveStream\StreamMessageSent;
use App\Events\LiveStream\StreamMessagePinned;
use App\Events\LiveStream\StreamMessageDeleted;
use App\Events\LiveStream\StreamMessageUnpinned;
use App\Events\LiveStream\UserBlockedFromStream;
use App\Events\LiveStream\UserUnblockedFromStream;

class LiveStreamChatService
{
    public function sendMessage(AgoraChannel $stream, User $user, string $message, array $options = []): ?AgoraChannelMessage
    {
        try {
            // Punishment check
            if ($user->has_active_punishment) {
                throw new Exception('Cezalı olduğunuz için mesaj gönderemiyorsunuz.');
            }

            // Rate limiti kontrolü
            if (!$this->checkRateLimit($user->id, $stream->id)) {
                throw new Exception('Arka arkaya mesaj gönderemezsiniz.');
            }

            // Kullanıcı yasaklı mı kontrolü
            if ($this->isUserBlocked($user->id, $stream)) {
                throw new Exception('Engellendiniz.');
            }

            // Yayın için yasaklı kelime kontrolü
            if ($this->containsBlockedWords($message, $stream)) {
                throw new Exception('Yasaklı kelime kullanıldı.');
            }

            $userData = [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'username' => $user->username,
                'bio' => $user->bio ?? null,
                'is_approved' => $user->is_approved ?? false,
            ];

            $messageData = [
                'agora_channel_id' => $stream->id,
                'user_id' => $user->id,
                'user_data' => $userData,
                'message' => $message,
                'timestamp' => now(),
            ];

            // Uygulamada yasaklanan kelimeleri içeriyor ise sansür ekle
            $hasBannedWord = BannedWord::hasBannedWord($message);
            if ($hasBannedWord) {
                $censoredContent = BannedWord::censor($message);
                $messageData['message'] = $censoredContent;
                $messageData['original_message'] = $message;
                $messageData['has_banned_word'] = true;
            }

            // Mesaj oluşturma
            $chatMessage = AgoraChannelMessage::create($messageData);

            // Hediye mesajı ise
            if (isset($options['gift_id']) && $options['gift_id']) {
                $gift = Gift::find($options['gift_id']);
                if ($gift) {
                    $chatMessage->gift_id = $gift->id;
                    $chatMessage->gift_data = [
                        'id' => $gift->id,
                        'name' => $gift->name,
                        'slug' => $gift->slug,
                        'quantity' => $options['gift_quantity'] ?? 1,
                    ];
                }
            }

            // Sticker mesajı ise
            if (isset($options['sticker_id']) && $options['sticker_id']) {
                $chatMessage->sticker_id = $options['sticker_id'];
            }

            // Yanıt mesajı ise
            if (isset($options['parent_message_id']) && $options['parent_message_id']) {
                $parentMessage = AgoraChannelMessage::find($options['parent_message_id']);
                if ($parentMessage) {
                    $chatMessage->parent_message_id = $parentMessage->_id;
                }
            }

            $chatMessage->save();

            // Mesaj gönderme olayı tetikleme
            Event::dispatch(new StreamMessageSent($stream, $chatMessage));

            // Mesaj sayısını artır
            $this->incrementMessageCount($user->id, $stream->id);

            return $chatMessage;
        } catch (Exception $e) {
            Log::error('Failed to send message', [
                'stream_id' => $stream->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function sendMessageByAdmin(AgoraChannel $stream, Admin $admin, string $message, array $options = []): ?AgoraChannelMessage
    {
        try {
            // Mesaj oluşturma
            $chatMessage = AgoraChannelMessage::create([
                'agora_channel_id' => $stream->id,
                'admin_id' => $admin->id,
                'message' => $message,
                'timestamp' => now(),
            ]);


            // Mesaj gönderme olayı tetikleme
            Event::dispatch(new StreamMessageSent($stream, $chatMessage));

            return $chatMessage;
        } catch (Exception $e) {
            Log::error('Failed to send message by admin', [
                'stream_id' => $stream->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Mesajı siler
     *
     * @param AgoraChannelMessage $message
     * @param User $user
     * @return bool
     */
    public function deleteMessage(AgoraChannelMessage $message, User $user): bool
    {
        try {
            // Yetkili mi kontrolü (mesaj sahibi veya yayın sahibi veya moderatör)
            $stream = AgoraChannel::find($message->agora_channel_id);

            if (!$stream) {
                throw new Exception('Stream not found');
            }

            $canDelete = $message->user_id == $user->id ||
                $stream->user_id == $user->id ||
                $this->isUserModerator($user->id, $stream);

            if (!$canDelete) {
                throw new Exception('User is not authorized to delete this message');
            }

            // Mesaj silme olayı tetikleme
            Event::dispatch(new StreamMessageDeleted($message->agora_channel_id, $message->_id, $user->id));

            // Mesajı sil
            $message->delete();

            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete message', [
                'message_id' => $message->_id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Mesajı pinler/unpinler
     *
     * @param AgoraChannelMessage $message
     * @param User $user
     * @param bool $isPinned
     * @return bool
     */
    public function pinMessage(AgoraChannelMessage $message, User $user, bool $isPinned = true): bool
    {
        try {
            // Yetkili mi kontrolü (yayın sahibi veya moderatör)
            $stream = AgoraChannel::find($message->agora_channel_id);

            if (!$stream) {
                throw new \Exception('Stream not found');
            }

            $canPin = $stream->user_id == $user->id ||
                $this->isUserModerator($user->id, $stream);

            if (!$canPin) {
                throw new \Exception('User is not authorized to pin messages');
            }

            // Önceki pinlenmiş mesajı kaldır
            if ($isPinned) {
                AgoraChannelMessage::where('agora_channel_id', $stream->id)
                    ->where('is_pinned', true)
                    ->update(['is_pinned' => false]);
            }

            // Mesajı güncelle
            $message->is_pinned = $isPinned;
            $message->save();

            // Mesaj pinleme/unpinleme olayı tetikleme
            if ($isPinned) {
                Event::dispatch(new StreamMessagePinned($stream, $message, $user->id));
            } else {
                Event::dispatch(new StreamMessageUnpinned($stream->id, $user->id));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to pin/unpin message', [
                'message_id' => $message->_id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function blockUser(AgoraChannel $stream, User $moderator, int $targetUserId, bool $isBlocked = true): bool
    {
        try {
            // Yetkili mi kontrolü (yayın sahibi veya moderatör)
            $canBlock = $stream->user_id == $moderator->id ||
                $this->isUserModerator($moderator->id, $stream);

            if (!$canBlock) {
                throw new \Exception('User is not authorized to block users');
            }

            // Yayın sahibini engelleyemez
            if ($targetUserId == $stream->user_id) {
                throw new \Exception('Cannot block stream owner');
            }

            // Stream ayarlarını güncelle
            $settings = $stream->settings ?? [];
            $blockedUsers = $settings['blocked_users'] ?? [];

            if ($isBlocked) {
                if (!in_array($targetUserId, $blockedUsers)) {
                    $blockedUsers[] = $targetUserId;
                }
            } else {
                $blockedUsers = array_filter($blockedUsers, function ($id) use ($targetUserId) {
                    return $id != $targetUserId;
                });
            }

            $settings['blocked_users'] = $blockedUsers;
            $stream->settings = $settings;
            $stream->save();

            // İzleyiciyi güncelle
            if ($isBlocked) {
                $this->updateBlockedViewerStatus($stream->id, $targetUserId);

                // Kullanıcı engelleme olayını tetikleme
                Event::dispatch(new UserBlockedFromStream($stream, $targetUserId, $moderator->id));
            } else {
                // Kullanıcı engel kaldırma olayını tetikleme
                Event::dispatch(new UserUnblockedFromStream($stream, $targetUserId, $moderator->id));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to block/unblock user', [
                'stream_id' => $stream->id,
                'moderator_id' => $moderator->id,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function assignModerator(AgoraChannel $stream, User $owner, int $targetUserId, bool $isModerator = true): bool
    {
        try {
            // Yayın sahibi mi kontrolü
            if ($stream->user_id != $owner->id) {
                throw new \Exception('Only stream owner can assign moderators');
            }

            // Kendisini moderatör yapamaz
            if ($targetUserId == $owner->id) {
                throw new \Exception('Stream owner is already a moderator');
            }

            // Stream ayarlarını güncelle
            $settings = $stream->settings ?? [];
            $moderators = $settings['moderator_users'] ?? [];

            if ($isModerator) {
                if (!in_array($targetUserId, $moderators)) {
                    $moderators[] = $targetUserId;
                }
            } else {
                $moderators = array_filter($moderators, function ($id) use ($targetUserId) {
                    return $id != $targetUserId;
                });
            }

            $settings['moderator_users'] = $moderators;
            $stream->settings = $settings;
            $stream->save();

            // Moderatör atama olayını tetikleme
            if ($isModerator) {
                Event::dispatch(new ModeratorAssigned($stream, $targetUserId, $owner->id));
            } else {
                Event::dispatch(new ModeratorRemoved($stream, $targetUserId, $owner->id));
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to assign/remove moderator', [
                'stream_id' => $stream->id,
                'owner_id' => $owner->id,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function getStreamMessages(string $streamId, int $page = 1, int $limit = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AgoraChannelMessage::where('agora_channel_id', $streamId)
            ->orderBy('timestamp', 'desc');

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function getPinnedMessage(string $streamId): ?AgoraChannelMessage
    {
        return AgoraChannelMessage::where('agora_channel_id', $streamId)
            ->where('is_pinned', true)
            ->first();
    }

    protected function checkRateLimit($userId, $streamId): bool
    {
        $key = "chat_rate_limit:{$userId}:{$streamId}";
        $count = Redis::get($key) ?: 0;

        // Saniyede 3 mesaj limiti
        if ($count >= 3) {
            return false;
        }

        Redis::incr($key);
        Redis::expire($key, 1); // 1 saniye

        return true;
    }

    protected function incrementMessageCount($userId, $streamId): void
    {
        $viewer = AgoraChannelViewer::where('agora_channel_id', $streamId)
            ->where('user_id', $userId)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->first();

        if ($viewer) {
            $viewer->messages_count = ($viewer->messages_count ?? 0) + 1;
            $viewer->save();
        }
    }

    protected function containsBlockedWords(string $message, AgoraChannel $stream): bool
    {
        $settings = $stream->settings ?? [];
        $blockedWords = $settings['blocked_words'] ?? [];

        if (empty($blockedWords)) {
            return false;
        }

        $message = strtolower($message);

        foreach ($blockedWords as $word) {
            if (strpos($message, strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function isUserBlocked($userId, AgoraChannel $stream): bool
    {
        $settings = $stream->settings ?? [];
        $blockedUsers = $settings['blocked_users'] ?? [];

        return in_array($userId, $blockedUsers);
    }

    protected function isUserModerator(int $userId, AgoraChannel $stream): bool
    {
        // Yayın sahibi her zaman moderatördür
        if ($userId == $stream->user_id) {
            return true;
        }

        $settings = $stream->settings ?? [];
        $moderators = $settings['moderator_users'] ?? [];

        return in_array($userId, $moderators);
    }

    protected function updateBlockedViewerStatus(string $streamId, int $userId): void
    {
        $viewer = AgoraChannelViewer::where('agora_channel_id', $streamId)
            ->where('user_id', $userId)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->first();

        if ($viewer) {
            $viewer->status = AgoraChannelViewer::STATUS_BANNED;
            $viewer->left_at = now();
            $viewer->save();
        }
    }
}
