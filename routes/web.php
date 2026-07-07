<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PlacesController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\BookingPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reservation');

// Tunnel de réservation.
Route::get('/reservation', [BookingPageController::class, 'showForm'])->name('booking.form');
Route::post('/reservation', [BookingPageController::class, 'submitStep1'])->name('booking.step1');
Route::get('/ma-reservation', [BookingPageController::class, 'showSteps'])->name('booking.steps');

// API interne du tunnel (même origine, protégée par CSRF sur les POST).
Route::prefix('api/v1')->group(function () {
    Route::get('autocomplete', [PlacesController::class, 'autocomplete'])->middleware('throttle:30,1');
    Route::post('quotes', [QuoteController::class, 'quotes'])->middleware('throttle:20,1');
    Route::post('booking', [BookingController::class, 'store'])->middleware('throttle:10,1');
});
