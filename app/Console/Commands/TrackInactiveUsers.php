<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Events\UserOffline;
use App\Models\UserSessionLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TrackInactiveUsers extends Command
{
    protected $signature = 'users:track-inactive';
    protected $description = '40 saniyeden fazla aktif olmayan kullanıcıları session log tablosuna kaydet ve Redis temizle';

    public function handle()
    {
        $now = Carbon::now();
        $iterator = null;
        $pattern = 'socket-user:*';
        $count = 100;

        do {
            $scanResult = Redis::scan($iterator, ['match' => $pattern, 'count' => $count]);

            if ($scanResult === false) {
                break;
            }

            [$iterator, $keys] = $scanResult;

            if (empty($keys)) {
                continue;
            }

            foreach ($keys as $key) {
                $dataJson = Redis::get($key);
                if (!$dataJson) continue;

                $data = json_decode($dataJson, true);
                $userId = $data['user_id'] ?? null;
                $startAt = $data['start_at'] ?? null;
                $lastSeenAt = $data['last_seen_at'] ?? null;

                $socketCountKey = "active-socket-count:user:{$userId}";

                if (!$userId || !$startAt || !$lastSeenAt) {
                    Redis::del($key);
                    Redis::decr($socketCountKey);
                    continue;
                }

                try {
                    $startAtCarbon = Carbon::parse($startAt);
                    $lastSeenCarbon = Carbon::parse($lastSeenAt);
                } catch (Exception $e) {
                    Log::warning("TrackInactiveUsers: Carbon parse error", [
                        'key' => $key,
                        'data' => $data,
                        'error' => $e->getMessage(),
                    ]);
                    Redis::decr($socketCountKey);
                    Redis::del($key);
                    continue;
                }

                $diffInSeconds = $lastSeenCarbon->diffInSeconds($now);
                if ($diffInSeconds > 40) {
                    if (!$startAtCarbon->equalTo($lastSeenCarbon)) {
                        UserSessionLog::create([
                            'user_id' => $userId,
                            'start_at' => $startAtCarbon,
                            'end_at' => $lastSeenCarbon,
                        ]);
                    }

                    Redis::del($key);
                    Redis::decr($socketCountKey);

                    $socketCount = (int) Redis::get($socketCountKey);
                    if ($socketCount <= 0) {
                        Redis::srem("active-users", $userId);
                        Redis::del($socketCountKey);
                    }


                    $user = User::find($userId);
                    if ($user) {
                        $receivers = $user->allConversationsUserIds() ?? [];
                        foreach ($receivers as $receiverId) {
                            event(new UserOffline($receiverId));
                        }
                    }

                    $this->info("User session logged and Redis key deleted: {$key}");
                }
            }
        } while ($iterator != 0);

        return 0;
    }
}
