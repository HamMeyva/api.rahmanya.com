<?php

use App\Http\Controllers\Api\v1\VideoController;
use App\Http\Controllers\Api\v1\BunnyWebhookController;
use Illuminate\Support\Facades\Route;

// BunnyCDN related endpoints
Route::prefix('bunny-cdn')->controller(VideoController::class)->group(function () {

    Route::get('/list', 'listVideos');
    Route::get('/get-video-data/{videoId}', 'getVideoPlayData');

});

// BunnyCDN webhook endpoint (no authentication required as it comes from BunnyCDN)
Route::post('/webhook/bunny-video-status', [BunnyWebhookController::class, 'handleVideoStatusChange']);

// Video related endpoints
Route::prefix('video')->middleware('auth:sanctum')
    ->controller(VideoController::class)->group(function () {

        Route::get('/{videoId}', 'show');
        Route::post('/{videoId}/like', 'likeVideo');

        Route::get('/{videoId}/comments', 'comments');
        Route::post('/{videoId}/add-comment', 'commentVideo');
        Route::post('/{videoId}/{comment}/reply', 'replyToComment');


        Route::post('/{videoId}/update', 'updateVideoData');

        Route::post('/report-video/{videoId}', 'reportVideo');


    });
