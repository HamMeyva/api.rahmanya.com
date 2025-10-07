<?php

use App\Http\Controllers\Api\v1\ProfileVisitController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'profile'], function () {

    Route::group(['middleware' => 'auth:sanctum'], function () {

        Route::get('/{userId}', ProfileVisitController::class);

    });

});
