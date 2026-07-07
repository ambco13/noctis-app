<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Crée une réservation en attente de paiement. Le prix est recalculé
     * côté serveur ; rien de ce qui vient du client n'est cru sur parole.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:40'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'pickup_place_id' => ['nullable', 'string', 'max:255'],
            'dropoff_place_id' => ['nullable', 'string', 'max:255'],
            'ride_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'ride_time' => ['required', 'date_format:H:i'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['nullable', 'string', 'in:stripe,paypal'],
        ]);

        $booking = BookingService::createPending($validated);

        return response()->json([
            'booking_id' => $booking->id,
            'summary' => BookingService::publicSummary($booking),
        ], 201);
    }
}
