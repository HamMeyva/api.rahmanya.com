<?php

use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::post('/create-account', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/password/email', [AuthController::class, 'sendResetPasswordLink']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    Route::post('/phone/verify', [AuthController::class, 'verifyPhone']);
    Route::post('/phone/send-otp', [AuthController::class, 'sendOTP']);

    Route::get('/all-teams', [AuthController::class, 'fetchAllTeams']);
});
