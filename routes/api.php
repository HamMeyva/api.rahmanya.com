<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\VideoProcessWebhookController;

Route::group(['prefix' => 'v1'], function () {

    include 'group/payments.php';

    include 'group/users.php';

    include 'group/agora.php';

    include 'group/video.php';

    include 'group/common.php';

    include 'group/profile.php';

    // Mixer API routes
    include 'api/mixer.php';

    // LiveStream Invite routes
    include 'api/livestream-invites.php';

    // Simple invite routes (for testing)
    include 'api/simple-invites.php';

    // Co-host routes
    include 'api/cohost.php';

    // Room cohost management routes
    include 'api/room.php';

    // LiveStream Chat & Gift routes
    include 'api/livestream.php';

    // Public endpoint for getting active cohosts (no auth needed for read-only)
    Route::get('/livestream/cohost/active-public/{channel_name}', [App\Http\Controllers\Api\LiveStream\CohostStreamController::class, 'getActiveCoHostsPublic']);

    // Debug endpoint for checking cohost stream data
    Route::get('/debug/cohost/{streamId}', [App\Http\Controllers\Api\Debug\DebugCohostController::class, 'checkCohostStream']);

    // Stream viewer routes
    include 'api/stream-viewer.php';

    Route::post('/video-process/callback', [VideoProcessWebhookController::class, 'callback'])->name('video-process.callback');

    // Batch Upload Routes
    Route::prefix('batch-upload')->group(function () {
        Route::post('/initiate', [App\Http\Controllers\Api\v1\GiftBatchUploadController::class, 'initiateBatchUpload']);
        Route::post('/batch', [App\Http\Controllers\Api\v1\GiftBatchUploadController::class, 'uploadBatch']);
        Route::post('/finalize', [App\Http\Controllers\Api\v1\GiftBatchUploadController::class, 'finalizeBatchUpload']);
        Route::get('/status', [App\Http\Controllers\Api\v1\GiftBatchUploadController::class, 'getUploadStatus']);
    });

    // Gift frame URLs endpoint - separate from WebSocket to avoid payload limits
    Route::get('/gifts/{giftId}/frame-urls', function ($giftId) {
        $gift = \App\Models\Gift::find($giftId);
        
        if (!$gift || !$gift->is_frame_animation) {
            return response()->json(['error' => 'Gift not found or not frame animation'], 404);
        }
        
        // Try to get frame URLs from multiple sources
        $frameUrls = [];
        
        // 1. Try getOrderedFrameUrls() which gets from assets
        $frameUrls = $gift->getOrderedFrameUrls();
        
        // 2. If empty, try frame_urls attribute (from frame_paths)
        if (empty($frameUrls)) {
            $frameUrls = $gift->frame_urls ?? [];
        }
        
        // 3. If still empty, check frame_paths directly
        if (empty($frameUrls) && !empty($gift->frame_paths)) {
            $bunnyCdnService = app(\App\Services\BunnyCdnService::class);
            foreach ($gift->frame_paths as $framePath) {
                $frameUrls[] = $bunnyCdnService->getStorageUrl($framePath);
            }
        }
        
        // Log for debugging
        \Log::info('🎁 API: Frame URLs request', [
            'gift_id' => $gift->id,
            'gift_name' => $gift->name,
            'frame_count' => $gift->frame_count,
            'frame_urls_count' => count($frameUrls),
            'has_frame_paths' => !empty($gift->frame_paths),
            'has_frame_assets' => $gift->frameAssets()->exists(),
            'first_url' => $frameUrls[0] ?? null,
            'last_url' => end($frameUrls) ?: null,
        ]);
        
        return response()->json([
            'gift_id' => $gift->id,
            'frame_urls' => $frameUrls,
            'frame_count' => $gift->frame_count,
            'animation_duration' => $gift->animation_duration,
            'frame_rate' => $gift->frame_rate,
            'animation_style' => $gift->animation_style,
        ]);
    });

    // ZIP gift endpoint optimized for mobile Flutter
    Route::get('/gifts/{giftId}/zip-animation', function ($giftId) {
        $gift = \App\Models\Gift::find($giftId);
        
        if (!$gift || !$gift->is_zip_animation || !$gift->zip_path) {
            return response()->json(['error' => 'Gift not found or not ZIP animation'], 404);
        }
        
        return response()->json([
            'gift_id' => $gift->id,
            'zip_url' => $gift->zip_url,
            'zip_frame_count' => $gift->zip_frame_count,
            'animation_duration' => $gift->animation_duration,
            'frame_rate' => $gift->frame_rate,
            'animation_style' => $gift->animation_style,
            'compression_info' => [
                'type' => 'deflate',
                'level' => 3,
                'mobile_optimized' => true
            ]
        ]);
    });

    Broadcast::routes();

    // Broadcast test endpoints
    Route::post('/broadcast/test', [App\Http\Controllers\Api\v1\BroadcastTestController::class, 'testBroadcast']);
    Route::post('/broadcast/fix', [App\Http\Controllers\Api\v1\BroadcastTestController::class, 'fixBroadcast']);
});    include 'group/zego.php';
