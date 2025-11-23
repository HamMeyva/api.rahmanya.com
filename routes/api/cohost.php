<?php

use App\Http\Controllers\Api\LiveStream\CohostStreamController;
use App\Http\Controllers\Api\LiveStream\CohostRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('livestream/cohost')->middleware('auth:sanctum')->group(function () {
    // Accept invite and start co-host stream
    Route::post('/accept-invite', [CohostStreamController::class, 'acceptInviteAndStartStream']);

    // Decline invite
    Route::post('/decline-invite', [CohostStreamController::class, 'declineInvite']);

    // Leave co-host session
    Route::post('/leave', [CohostStreamController::class, 'leaveCohostSession']);

    // Get active co-hosts for a stream
    Route::get('/active/{channel_name}', [CohostStreamController::class, 'getActiveCoHosts']);

    // Remove a co-host (host only)
    Route::post('/remove', [CohostStreamController::class, 'removeCoHost']);

    // New endpoints for proper cohost registration and synchronization
    Route::post('/register-join', [CohostRegistrationController::class, 'registerCohostJoin']);
    Route::post('/notify-leave', [CohostRegistrationController::class, 'notifyCohostLeave']);

    // Message relay endpoint for cross-room synchronization
    Route::post('/message/relay', [CohostRegistrationController::class, 'relayMessage']);
});