<?php

namespace App\Services\LiveStream;

use App\Models\LiveStreamParticipant;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Facades\Event;

class StreamParticipantService
{
    public function addParticipant(string $streamId, string $userId, string $participantType = 'guest'): LiveStreamParticipant
    {
        $channel = AgoraChannel::where('id', $streamId)->orWhere('channel_name', $streamId)->firstOrFail();

        if (($channel->mode ?? 'normal') !== 'multi_guest') {
            throw new \RuntimeException('Stream multi-guest mode\'da değil');
        }

        $activeCount = LiveStreamParticipant::where('live_stream_id', (string)$channel->id)
            ->where('is_active', true)
            ->count();

        if ($activeCount >= (int)($channel->max_participants ?? 1)) {
            throw new \RuntimeException('Maksimum katılımcı sayısı aşıldı');
        }

        $participant = LiveStreamParticipant::updateOrCreate(
            [
                'live_stream_id' => (string)$channel->id,
                'user_id' => $userId,
            ],
            [
                'role' => 'guest',
                'participant_type' => $participantType,
                'is_active' => true,
            ]
        );

        Event::dispatch(new \App\Events\LiveStream\ViewerJoined((string)$channel->id, $userId));

        return $participant;
    }

    public function removeParticipant(string $streamId, string $userId): bool
    {
        $channel = AgoraChannel::where('id', $streamId)->orWhere('channel_name', $streamId)->firstOrFail();

        $affected = LiveStreamParticipant::where('live_stream_id', (string)$channel->id)
            ->where('user_id', $userId)
            ->update(['is_active' => false, 'left_at' => now()]);

        if ($affected > 0) {
            Event::dispatch(new \App\Events\LiveStream\ViewerLeft((string)$channel->id, $userId));
        }

        return $affected > 0;
    }

    public function updateParticipantSettings(string $streamId, string $userId, array $settings): LiveStreamParticipant
    {
        $channel = AgoraChannel::where('id', $streamId)->orWhere('channel_name', $streamId)->firstOrFail();

        $participant = LiveStreamParticipant::where('live_stream_id', (string)$channel->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $updates = [];
        if (array_key_exists('audioEnabled', $settings)) {
            $updates['audio_enabled'] = (bool)$settings['audioEnabled'];
        }
        if (array_key_exists('videoEnabled', $settings)) {
            $updates['video_enabled'] = (bool)$settings['videoEnabled'];
        }

        if (!empty($updates)) {
            $participant->update($updates);
        }

        return $participant->fresh();
    }
}


