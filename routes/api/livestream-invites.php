<?php

use App\Http\Controllers\Api\LiveStream\LiveStreamInviteController;
use Illuminate\Support\Facades\Route;

Route::prefix('livestream/invites')->middleware('auth:sanctum')->group(function () {
    // Send invite
    Route::post('/send', [LiveStreamInviteController::class, 'sendInvite']);

    // Accept invite
    Route::post('/accept', [LiveStreamInviteController::class, 'acceptInvite']);

    // Decline invite
    Route::post('/decline', [LiveStreamInviteController::class, 'declineInvite']);

    // Get pending invites
    Route::get('/pending', [LiveStreamInviteController::class, 'getPendingInvites']);

    // Remove co-host
    Route::post('/remove-cohost', [LiveStreamInviteController::class, 'removeCohost']);
});