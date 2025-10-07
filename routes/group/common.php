<?php

use App\Http\Controllers\Api\v1\ContactFormController;
use App\Http\Controllers\Api\v1\FaqController;
use App\Http\Controllers\Api\v1\GiftController;
use App\Http\Controllers\Api\v1\PageController;
use App\Http\Controllers\Api\v1\ShortenedUrlController;
use Illuminate\Support\Facades\Route;

// Pages
Route::prefix('page')->controller(PageController::class)->group(function () {
    Route::get('/', '__invoke');
    Route::get('/{slug}', 'show');
});

// FAQ
Route::prefix('faq')->controller(FaqController::class)->group(function () {
    Route::get('/', '__invoke');
    Route::get('/{slug}', 'show');
});

// GIFTS
Route::prefix('gifts')->controller(GiftController::class)->group(function () {
    Route::get('/', '__invoke');
});

// SHORTENED URL
Route::prefix('link')->controller(ShortenedUrlController::class)->group(function () {
    Route::post('/shorten', 'shorten');
    Route::get('/{shortCode}', 'redirect');
});

Route::post('contact-form', ContactFormController::class);
