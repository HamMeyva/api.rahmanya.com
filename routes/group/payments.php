<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\PaymentController;


Route::group(['prefix' => 'payments', 'as' => 'payments.'], function () {
    Route::group(['prefix' => 'iyzico', 'as' => 'iyzico.'], function () {
        Route::post('/threed-callback', [PaymentController::class, 'threedCallback'])->name('threed-callback');
    });
});