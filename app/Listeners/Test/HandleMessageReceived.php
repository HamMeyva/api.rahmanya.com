<?php

namespace App\Listeners\Test;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Events\MessageReceived;

class HandleMessageReceived
{
    public function handle(MessageReceived $event): void
    {
        try {
            $socketId = $event->connection->id();
            $lastSeenAt = $event->connection->lastSeenAt();

            $key = "socket-user:{$socketId}";
            $existingDataJson = Redis::get($key);
            if (!$existingDataJson) return;

            $existingData = json_decode($existingDataJson, true);

            $existingData['last_seen_at'] = $lastSeenAt;

            Redis::setex($key, 86400, json_encode($existingData));

            //Log::info("HandleMessageReceived: updated {$key}", $existingData);
        } catch (Exception $e) {
            Log::error('HandleMessageReceived işlenirken hata oluştu: ' . $e->getMessage());
        }
    }
}
