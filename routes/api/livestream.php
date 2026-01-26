<?php

use App\Http\Controllers\Api\LiveStream\LiveStreamChatController;
use App\Http\Controllers\Api\LiveStream\LiveStreamGiftController;
use App\Http\Controllers\Api\LiveStream\LiveStreamController;
use Illuminate\Support\Facades\Route;

/**
 * Live Stream Routes
 * Base: /api/v1/livestream
 */

Route::prefix('livestream')->middleware('auth:sanctum')->group(function () {

    // Stream management - Read only
    Route::get('/{id}', [LiveStreamController::class, 'show']); // Stream bilgisi

    // Routes that require can.interact middleware (blocked for users with red card)
    Route::middleware('can.interact')->group(function () {
        // Stream management - Write operations
        Route::post('/start', [LiveStreamController::class, 'store']); // Go Live - Yayın başlat
        Route::post('/{id}/go-live', [LiveStreamController::class, 'goLive']); // Stream'i aktif yap

        // Chat - Send messages
        Route::post('/chat/{streamId}/messages', [LiveStreamChatController::class, 'store']);
    });

    // Stream end (allowed even for red card users)
    Route::post('/{id}/end', [LiveStreamController::class, 'endStream']); // Yayını bitir

    // Chat endpoints - Read only
    Route::prefix('chat/{streamId}')->controller(LiveStreamChatController::class)->group(function () {
        Route::get('/messages', 'index');
        Route::get('/pinned', 'getPinnedMessage');
    });

    // Gift endpoints
    Route::prefix('gifts')->controller(LiveStreamGiftController::class)->group(function () {
        Route::get('/available', 'availableGifts');
        Route::post('/{streamId}', 'store');
        Route::get('/{streamId}', 'index');
        Route::get('/{streamId}/top-donators', 'topDonators');
    });

    // Chat moderation endpoints
    Route::post('/chat/messages/{messageId}/pin', [LiveStreamChatController::class, 'pin']);
    Route::delete('/chat/messages/{messageId}', [LiveStreamChatController::class, 'destroy']);
    Route::post('/chat/{streamId}/block-user/{userId}', [LiveStreamChatController::class, 'blockUser']);
    Route::post('/chat/{streamId}/assign-moderator/{userId}', [LiveStreamChatController::class, 'assignModerator']);
});
