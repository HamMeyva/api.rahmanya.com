<?php

namespace App\Services\User;

use Illuminate\Support\Facades\Redis;

class OnlineUserService
{
    public static function markOnline($userId): void
    {
        Redis::set("user:{$userId}:is_online", now()->timestamp, 'EX', 60);
        Redis::sadd('online_users', $userId);
    }

    public static function markOffline($userId): void
    {
        Redis::del("user:{$userId}:is_online");
        Redis::srem('online_users', $userId);
    }

    public static function getOnlineUserCount(): int
    {
        return Redis::scard('online_users');
    }

    public static function getOnlineUserIds(): array
    {
        return Redis::smembers('online_users');
    }

    public static function isOnline($userId): bool
    {
         return Redis::exists("user:{$userId}:is_online") === 1;
    }
}
