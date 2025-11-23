<?php

use App\Http\Controllers\Api\LiveStream\SimpleLiveStreamInviteController;
use Illuminate\Support\Facades\Route;

Route::prefix('livestream/simple-invites')->middleware('auth:sanctum')->group(function () {
    // Send a simple invite (doesn't start any streams)
    Route::post('/send', [SimpleLiveStreamInviteController::class, 'sendSimpleInvite']);

    // Get my pending invites
    Route::get('/my-invites', [SimpleLiveStreamInviteController::class, 'getMyInvites']);

    // Cancel an invite (for host)
    Route::post('/cancel', [SimpleLiveStreamInviteController::class, 'cancelInvite']);
});