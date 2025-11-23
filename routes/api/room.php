<?php

use App\Http\Controllers\Api\Room\RoomCohostController;
use Illuminate\Support\Facades\Route;

// Room cohost management endpoints
Route::prefix('room/{roomId}')->group(function () {
    // Register cohost when joining
    Route::post('/register-cohost', [RoomCohostController::class, 'registerCohost']);

    // Get all active cohosts in room
    Route::get('/active-cohosts', [RoomCohostController::class, 'getActiveCohosts']);

    // Update heartbeat (keep-alive)
    Route::post('/heartbeat', [RoomCohostController::class, 'heartbeat']);

    // Remove cohost (when leaving)
    Route::post('/remove-cohost', [RoomCohostController::class, 'removeCohost']);

    // Cleanup inactive cohosts (admin/scheduled task)
    Route::post('/cleanup-cohosts', [RoomCohostController::class, 'cleanupInactiveCohosts'])
        ->middleware('auth:sanctum');

    // Get all participants in room (host + cohosts)
    Route::get('/participants', [\App\Http\Controllers\Api\LiveStream\LiveStreamController::class, 'getRoomParticipants']);
});