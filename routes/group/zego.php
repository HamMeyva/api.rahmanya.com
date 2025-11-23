<?php

use App\Http\Controllers\Api\Zego\ZegoCallInvitationController;
use App\Http\Controllers\ZegoMixerController;
use App\Http\Controllers\ZegoMixerWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Zego Call Invitation Callback URLs
 * Documentation: https://docs.zegocloud.com/article/15461
 */
Route::prefix('zego/call-invitation')->group(function () {
    
    // Send Call Invitation Callback
    Route::post('/sent', [ZegoCallInvitationController::class, 'handleCallInvitationSent'])
        ->name('zego.call-invitation.sent');
    
    // Cancel Call Invitation Callback
    Route::post('/cancelled', [ZegoCallInvitationController::class, 'handleCallInvitationCancelled'])
        ->name('zego.call-invitation.cancelled');
    
    // Accept Call Invitation Callback
    Route::post('/accepted', [ZegoCallInvitationController::class, 'handleCallInvitationAccepted'])
        ->name('zego.call-invitation.accepted');
    
    // Reject Call Invitation Callback
    Route::post('/rejected', [ZegoCallInvitationController::class, 'handleCallInvitationRejected'])
        ->name('zego.call-invitation.rejected');
    
    // Call Invitation Timed Out Callback
    Route::post('/timeout', [ZegoCallInvitationController::class, 'handleCallInvitationTimeout'])
        ->name('zego.call-invitation.timeout');
    
    // PK Battle specific callbacks
    Route::post('/pk-battle/invite', [ZegoCallInvitationController::class, 'handlePKBattleInvite'])
        ->name('zego.pk-battle.invite');
    
    Route::post('/pk-battle/response', [ZegoCallInvitationController::class, 'handlePKBattleResponse'])
        ->name('zego.pk-battle.response');
});

/**
 * Zego Mixer Routes for Co-host Stream Management
 */
Route::prefix('zego')->group(function () {
    // Notify when a co-host joins
    Route::post('/notify-cohost-joined', [ZegoMixerController::class, 'notifyCohostJoined'])
        ->name('zego.notify-cohost-joined');

    // Update existing mixer
    Route::post('/update-mixer', [ZegoMixerController::class, 'updateMixer'])
        ->name('zego.update-mixer');

    // Get mixer info
    Route::get('/mixer-info/{streamId}', [ZegoMixerController::class, 'getMixerInfo'])
        ->name('zego.mixer-info');

    // Mixer webhook handler
    Route::post('/mixer-webhook', [ZegoMixerWebhookController::class, 'handleMixerWebhook'])
        ->name('zego.mixer-webhook');

    // Get mixed stream for a room
    Route::get('/mixed-stream/{roomId}', [ZegoMixerWebhookController::class, 'getMixedStream'])
        ->name('zego.mixed-stream');
});
