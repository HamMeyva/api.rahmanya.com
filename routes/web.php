<?php

use Carbon\Carbon;
use App\Events\TestUserChannel;
use App\Models\UserSessionLog;
use App\Models\Chat\Conversation;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeeplinkController;
use App\Http\Controllers\Api\v1\VideoController;
use App\Http\Controllers\Api\RabbitMQTestController;
use App\Models\Video;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Helpers\CommonHelper;
use App\Models\User;

Route::get('/', function () {
    return '--';
});


Route::get('/redis-keys', function () {
    $result = [];

    // 1. socket-user:* key'leri
    $socketUserKeys = Redis::keys('socket-user:*');
    $result['socket_users'] = [];

    foreach ($socketUserKeys as $key) {
        $result['socket_users'][$key] = json_decode(Redis::get($key), true);
    }

    // 2. active-users set
    $activeUsers = Redis::smembers('active-users');
    $result['active_users'] = $activeUsers;

    // 3. active-socket-count:user:* key'leri
    $socketCountKeys = Redis::keys('active-socket-count:user:*');
    $result['socket_counts'] = [];

    foreach ($socketCountKeys as $key) {
        $result['socket_counts'][$key] = Redis::get($key);
    }

    return response()->json($result);
});

Route::get('/track-inactive-users-trigger', function () {
    // Komutu çalıştır
    Artisan::call('users:track-inactive');

    // İstersen çıktı al
    $output = Artisan::output();

    return response()->json([
        'message' => 'TrackInactiveUsers command executed',
        'output' => $output,
    ]);
});


Route::get('/redis-flush-sockets', function () {
    // 1. socket-user:* key'lerini sil
    $socketKeys = Redis::keys('socket-user:*');
    foreach ($socketKeys as $key) {
        Redis::del($key);
    }

    // 2. active-socket-count:user:* key'lerini sil
    $activeSocketCountKeys = Redis::keys('active-socket-count:user:*');
    foreach ($activeSocketCountKeys as $key) {
        Redis::del($key);
    }

    // 3. active-users set'ini tamamen sil
    Redis::del('active-users');

    return response()->json([
        'deleted_socket_keys_count' => count($socketKeys),
        'deleted_socket_count_keys_count' => count($activeSocketCountKeys),
        'active_users_deleted' => true,
    ]);
});



Route::get('/redis-pending-jobs', function () {
    $queueName = 'default'; // Buraya istediğin queue adını yazabilirsin

    // Redis listesinde bekleyen işler:
    // Kuyruk listesi: queues:{queue_name}
    $redisKey = "queues:{$queueName}";

    // En fazla 200 iş getir (LRANGE start 0 end 199)
    $jobsRaw = Redis::lrange($redisKey, 0, 199);

    $jobs = collect($jobsRaw)->map(function ($jobPayload) {
        $payload = json_decode($jobPayload, true);

        return [
            'id' => $payload['uuid'] ?? null,
            'queue' => $payload['queue'] ?? null,
            'connection' => $payload['connection'] ?? null,
            'attempts' => $payload['attempts'] ?? 0,
            'job' => $payload['job'] ?? null,
            'data' => $payload['data'] ?? null,
            'raw' => $jobPayload,
        ];
    });

    $html = '<!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>Bekleyen İşler (Queue: ' . e($queueName) . ')</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { border-collapse: collapse; width: 100%; font-size: 14px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
            th { background-color: #f3f3f3; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            code { white-space: pre-wrap; display: block; background: #f7f7f7; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h2>Bekleyen İşler (Queue: ' . e($queueName) . ')</h2>
        <table>
            <thead>
                <tr>
                    <th>ID (UUID)</th>
                    <th>Queue</th>
                    <th>Connection</th>
                    <th>Job Sınıfı</th>
                    <th>Deneme Sayısı (Attempts)</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($jobs as $job) {
        $html .= '<tr>
            <td>' . e($job['id']) . '</td>
            <td>' . e($job['queue']) . '</td>
            <td>' . e($job['connection']) . '</td>
            <td>' . e($job['job']) . '</td>
            <td>' . e($job['attempts']) . '</td>
            <td><code>' . e(json_encode($job['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</code></td>
        </tr>';
    }

    $html .= '</tbody></table></body></html>';

    return response($html);
});

Route::get('/redis-failed-jobs', function (FailedJobProviderInterface $provider) {
    $jobs = collect($provider->all())
        ->sortByDesc('failed_at')
        ->take(200);

    $html = '<!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>Son 200 Failed Job</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { border-collapse: collapse; width: 100%; font-size: 14px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
            th { background-color: #f3f3f3; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            code { white-space: pre-wrap; display: block; background: #f7f7f7; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h2>Son 200 Queue Hatası</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Queue</th>
                    <th>Connection</th>
                    <th style="width: 150px;">Hata Zamanı (Europe/Istanbul)</th>
                    <th>Exception</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($jobs as $job) {
        $html .= '<tr>
            <td>' . e($job->id) . '</td>
            <td>' . e($job->queue) . '</td>
            <td>' . e($job->connection) . '</td>
            <td>' . e(\Carbon\Carbon::parse($job->failed_at)->timezone('Europe/Istanbul')->format('Y-m-d H:i:s')) . '</td>
            <td><code>' . e(Str::limit($job->exception, 1000)) . '</code></td>
        </tr>';
    }

    $html .= '</tbody></table></body></html>';

    return response($html);
});

Route::get('/test', function () {
    $now = Carbon::now();

    $keys = Redis::keys('socket-user:*');

    echo "<h3>Aktif olmayan kullanıcılar (60 saniyeden fazla)</h3><ul>";

    foreach ($keys as $key) {
        $dataJson = Redis::get($key);
        if (!$dataJson) continue;

        $data = json_decode($dataJson, true);

        if (!isset($data['last_seen_at'])) continue;

        $lastSeen = Carbon::parse($data['last_seen_at']);
        $diffInSeconds = $lastSeen->diffInSeconds($now);

        if ($diffInSeconds > 60) {
            echo "<li><strong>User ID:</strong> {$data['user_id']} | <strong>Channel:</strong> {$data['channel']} | <strong>Start:</strong> {$data['start_at']} | <strong>Last Seen:</strong> {$data['last_seen_at']} | <strong>Fark:</strong> {$diffInSeconds} sn</li>";
        } else {
            echo "<li><em>User ID:</em> {$data['user_id']} | <em>Channel:</em> {$data['channel']} | <em>Start:</em> {$data['start_at']} | <em>Last Seen:</em> {$data['last_seen_at']} | <em>Fark:</em> {$diffInSeconds} sn</li>";
        }
    }

    echo "</ul>";


    $session = UserSessionLog::orderByDesc('start_at')->limit(50)->get();
    echo "<h3>En son 50 oturum</h3><ul>";
    foreach ($session as $item) {
        $user = $item->user();
        echo "<li><strong>User:</strong> {$user?->full_name} | <strong>Start:</strong> {$item->start_at} | <strong>Ended:</strong> {$item->ended_at} | <strong>Duration:</strong> {$item->duration} sn</li>";
    }
    echo "</ul>";



    return 'Tamamlandı.';
});

// Universal Links and App Links configuration
Route::get('/.well-known/apple-app-site-association', function () {
    return response()
        ->file(public_path('.well-known/apple-app-site-association'))
        ->header('Content-Type', 'application/json');
});

Route::get('/.well-known/assetlinks.json', function () {
    return response()
        ->file(public_path('.well-known/assetlinks.json'))
        ->header('Content-Type', 'application/json');
});

Route::prefix('api/rabbitmq')->group(function () {
    Route::get('/test-connection', [RabbitMQTestController::class, 'testConnection']);
    Route::get('/consume', [RabbitMQTestController::class, 'consumeMessage']);
    Route::post('/test-fallback', [RabbitMQTestController::class, 'testQueueWithFallback']);
    Route::get('/status', [RabbitMQTestController::class, 'queueStatus']);
});

// Deeplink Routes
Route::get('/v/{videoId}', [DeeplinkController::class, 'handleVideoDeeplink'])->name('deeplink.video');
Route::get('/u/{userId}', [DeeplinkController::class, 'handleProfileDeeplink'])->name('deeplink.profile');
Route::get('/share', [DeeplinkController::class, 'handleSharePage'])->name('deeplink.share');

Route::get('/test-mongo', function () {
    try {
        DB::connection('mongodb')->getMongoClient()->listDatabases();
        return "MongoDB bağlantısı başarılı!";
    } catch (\Exception $e) {
        return "Hata: " . $e->getMessage();
    }
});

Route::post('/test/{id}', [VideoController::class, 'show'])
    ->withoutMiddleware(VerifyCsrfToken::class);
Route::post('/test-user', function (Request $request) {
    $user = \App\Models\User::query()->with([
        'user_teams',
        'agora_channel',
        'primary_team',
        'followers',
        'following',
        'blocked_users',
        'taggable_users',
        'commentable_users',
        'videos',
        'visit_histories'
    ])->first();

    return response()->json([
        'success' => true,
        'response' => UserResource::make($user)
    ]);
})
    ->withoutMiddleware(VerifyCsrfToken::class);

Route::post('/test-user', function (Request $request) {
    $user = \App\Models\User::query()->with([
        'user_teams',
        'agora_channel',
        'primary_team',
        'followers',
        'following',
        'blocked_users',
        'taggable_users',
        'commentable_users',
        'videos',
        'visit_histories'
    ])->first();

    return response()->json([
        'success' => true,
        'response' => UserResource::make($user)
    ]);
})
    ->withoutMiddleware(VerifyCsrfToken::class);

Route::post('/test-video', function (Request $request) {
    $user = \App\Models\Video::query()->with([
        'video_comments',
        'reported_problems',
        'video_likes'
    ])->withCount([
        'video_likes',
        'video_comments',
    ])

        ->first();

    return response()->json([
        'success' => true,
        'response' => UserResource::make($user)
    ]);
})
    ->withoutMiddleware(VerifyCsrfToken::class);
