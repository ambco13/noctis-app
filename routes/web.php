<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PlacesController;
use App\Http\Controllers\Api\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API interne du tunnel de réservation (même origine, protégée par CSRF).
Route::prefix('api/v1')->group(function () {
    Route::post('autocomplete', [PlacesController::class, 'autocomplete'])->middleware('throttle:30,1');
    Route::post('quotes', [QuoteController::class, 'quotes'])->middleware('throttle:20,1');
    Route::post('booking', [BookingController::class, 'store'])->middleware('throttle:10,1');
});
