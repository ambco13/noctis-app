<?php

use App\Http\Controllers\AccountPageController;
use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\CustomerAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Admin\VehicleAdminController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlacesController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\BookingPageController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reservation');

// Tunnel de réservation.
Route::get('/reservation', [BookingPageController::class, 'showForm'])->name('booking.form');
Route::post('/reservation', [BookingPageController::class, 'submitStep1'])->name('booking.step1');
Route::get('/ma-reservation', [BookingPageController::class, 'showSteps'])->name('booking.steps');

// Espace client.
Route::get('/mon-compte', [AccountPageController::class, 'show'])->name('account');

// Back-office (auth + is_admin).
Route::prefix('admin')->middleware(['auth', EnsureAdmin::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/reservations', [BookingAdminController::class, 'index'])->name('admin.bookings');
    Route::get('/reservations/{booking}', [BookingAdminController::class, 'show'])->name('admin.bookings.show');
    Route::post('/reservations/{booking}/statut', [BookingAdminController::class, 'updateStatus'])->name('admin.bookings.status');
    Route::delete('/reservations/{booking}', [BookingAdminController::class, 'destroy'])->name('admin.bookings.destroy');
    Route::get('/clients', [CustomerAdminController::class, 'index'])->name('admin.customers');
    Route::get('/vehicules', [VehicleAdminController::class, 'index'])->name('admin.vehicles');
    Route::get('/vehicules/{vehicle}', [VehicleAdminController::class, 'edit'])->name('admin.vehicles.edit');
    Route::post('/vehicules', [VehicleAdminController::class, 'store'])->name('admin.vehicles.store');
    Route::put('/vehicules/{vehicle}', [VehicleAdminController::class, 'update'])->name('admin.vehicles.update');
    Route::delete('/vehicules/{vehicle}', [VehicleAdminController::class, 'destroy'])->name('admin.vehicles.destroy');
    Route::get('/reglages', [SettingsAdminController::class, 'edit'])->name('admin.settings');
    Route::post('/reglages', [SettingsAdminController::class, 'update'])->name('admin.settings.update');
    Route::post('/reglages/test', [SettingsAdminController::class, 'testService'])->name('admin.settings.test');
});

// API interne du tunnel (même origine, protégée par CSRF sur les POST).
Route::prefix('api/v1')->group(function () {
    Route::get('autocomplete', [PlacesController::class, 'autocomplete'])->middleware('throttle:30,1');
    Route::post('quotes', [QuoteController::class, 'quotes'])->middleware('throttle:20,1');
    Route::post('booking', [BookingController::class, 'store'])->middleware('throttle:10,1');
    Route::post('confirm/stripe', [PaymentController::class, 'confirmStripe'])->middleware('throttle:10,1');
    Route::post('paypal/order', [PaymentController::class, 'paypalCreateOrder'])->middleware('throttle:10,1');
    Route::post('paypal/capture', [PaymentController::class, 'paypalCapture'])->middleware('throttle:10,1');
    // Webhook appelé par Stripe (signature vérifiée dans le contrôleur, CSRF exclu dans bootstrap/app.php).
    Route::post('webhook/stripe', [PaymentController::class, 'webhookStripe']);

    // Auth clients (routes publiques — rate limiting fin dans le contrôleur).
    Route::post('auth/register', [CustomerAuthController::class, 'register']);
    Route::post('auth/signup', [CustomerAuthController::class, 'register']);
    Route::post('auth/check', [CustomerAuthController::class, 'check']);
    Route::post('auth/magic-link/request', [CustomerAuthController::class, 'magicLinkRequest']);
    Route::get('auth/magic-link/verify', [CustomerAuthController::class, 'magicLinkVerify'])->name('magic.verify');
    Route::post('auth/login', [CustomerAuthController::class, 'login']);
    Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
    Route::post('auth/password/forgot', [CustomerAuthController::class, 'passwordForgot']);
    Route::post('auth/password/reset', [CustomerAuthController::class, 'passwordReset']);

    // Espace client (connecté).
    Route::middleware('auth')->group(function () {
        Route::get('customer/me', [CustomerProfileController::class, 'show']);
        Route::patch('customer/me', [CustomerProfileController::class, 'update']);
        Route::post('customer/me/email', [CustomerProfileController::class, 'requestEmailChange']);
        Route::post('customer/me/password', [CustomerProfileController::class, 'setPassword']);
        Route::get('customer/me/export', [CustomerProfileController::class, 'export']);
        Route::post('customer/me/delete', [CustomerProfileController::class, 'destroy']);
        Route::get('customer/bookings', [CustomerProfileController::class, 'bookings']);
    });
});
