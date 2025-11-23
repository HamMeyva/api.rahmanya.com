<?php

use App\Http\Controllers\Api\MixerController;
use Illuminate\Support\Facades\Route;

Route::prefix('mixer')->middleware('auth:sanctum')->group(function () {
    // Get mixer session details
    Route::get('/session/{sessionId}', [MixerController::class, 'getSession']);

    // Get mixer by stream ID
    Route::get('/by-stream/{streamId}', [MixerController::class, 'getByStreamId']);

    // Get all active sessions
    Route::get('/active', [MixerController::class, 'getActiveSessions']);

    // Start new mixer
    Route::post('/start', [MixerController::class, 'startMixer']);

    // Update mixer
    Route::post('/update/{taskId}', [MixerController::class, 'updateMixer']);

    // Stop mixer
    Route::post('/stop/{taskId}', [MixerController::class, 'stopMixer']);

    // Handle streamer join
    Route::post('/streamer/join', [MixerController::class, 'handleStreamerJoin']);

    // Handle streamer leave
    Route::post('/streamer/leave', [MixerController::class, 'handleStreamerLeave']);

    // Admin: Reconfigure mixer
    Route::post('/reconfigure/{taskId}', [MixerController::class, 'reconfigureMixer'])
        ->middleware('role:admin');
});