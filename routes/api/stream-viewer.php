<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LiveStream\StreamViewerController;

Route::prefix('stream-viewer')->group(function () {
    // Get stream viewing options (mixed vs individual)
    Route::get('/options', [StreamViewerController::class, 'getStreamOptions']);

    // Get stream statistics
    Route::get('/stats', [StreamViewerController::class, 'getStreamStats']);

    // Update viewer count
    Route::post('/viewer-count', [StreamViewerController::class, 'updateViewerCount']);
});