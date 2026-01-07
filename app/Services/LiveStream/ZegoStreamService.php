<?php

namespace App\Services\LiveStream;

class ZegoStreamService
{
    public function generateStreamId(string $roomId, string $userId, string $role = 'main'): string
    {
        return $roomId . '_' . $userId . '_' . $role . '_' . time();
    }

    public function generateRoomId(): string
    {
        return 'room_' . uniqid() . '_' . time();
    }

    public function validateStreamCapacity(string $streamId, int $requestedParticipants): bool
    {
        return $requestedParticipants > 0;
    }
}


