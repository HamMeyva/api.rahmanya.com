<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\VideoProcessWebhookController;

Route::group(['prefix' => 'v1'], function () {

    include 'group/payments.php';

    include 'group/users.php';

    include 'group/agora.php';

    include 'group/video.php';

    include 'group/common.php';

    include 'group/profile.php';

    Route::post('/video-process/callback', [VideoProcessWebhookController::class, 'callback'])->name('video-process.callback');

    Broadcast::routes();
});
