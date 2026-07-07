<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleMaps;
use App\Services\Pricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    /**
     * Calcule le trajet puis le devis de chaque véhicule actif.
     */
    public function quotes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_address' => ['required', 'string', 'max:500'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'pickup_place_id' => ['nullable', 'string', 'max:255'],
            'dropoff_place_id' => ['nullable', 'string', 'max:255'],
            'ride_date' => ['nullable', 'date_format:Y-m-d'],
            'ride_time' => ['nullable', 'date_format:H:i'],
        ]);

        $route = GoogleMaps::computeRoute(
            $validated['pickup_address'],
            $validated['dropoff_address'],
            $validated['pickup_place_id'] ?? '',
            $validated['dropoff_place_id'] ?? ''
        );

        $context = [
            'ride_date' => $validated['ride_date'] ?? '',
            'ride_time' => $validated['ride_time'] ?? '',
        ];

        return response()->json([
            'route' => $route,
            'quotes' => Pricing::quotesForAllVehicles($route['distance_km'], $route['duration_min'], $context),
        ]);
    }
}
