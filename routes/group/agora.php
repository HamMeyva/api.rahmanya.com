<?php

use App\Http\Controllers\Api\v1\AgoraController;
use App\Http\Controllers\Api\LiveStream\LiveStreamController;
use App\Http\Controllers\Api\LiveStream\LiveStreamRecordingController;
use Illuminate\Support\Facades\Route;

// Eski API yapısı - geriye dönük uyumluluk için
Route::prefix('agora')->controller(AgoraController::class)->group(function () {
    Route::post('/channels-list', 'listChannels');
    Route::post('/leave-stream', 'setOffline');
    Route::post('/handle-stream-end', 'handleStreamEnd');
});

// Cloud Recording yeni API yapısı
Route::prefix('live-stream/recording')->middleware('auth:sanctum')->group(function () {
    Route::post('/start', [LiveStreamRecordingController::class, 'startRecording']);
    Route::post('/end', [LiveStreamRecordingController::class, 'handleStreamEnd']);
    Route::get('/{streamId}', [LiveStreamRecordingController::class, 'getRecordings']);
});

// Eski API'ları yeni LiveStreamController'a yönlendirme
Route::prefix('agora')->middleware('auth:sanctum')->group(function () {
    // create-stream artık store() metoduna yönlendiriliyor
    Route::post('/create-stream', [LiveStreamController::class, 'store']);
    // join-stream artık join() metoduna yönlendiriliyor
    Route::post('/join-stream', function(\Illuminate\Http\Request $request) {
        return app(LiveStreamController::class)->join($request, $request->input('channel_id'));
    });
});
