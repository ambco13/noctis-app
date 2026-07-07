<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Services\GoogleMaps;
use App\Services\Pricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    /**
     * Devis : distance/durée + prix de chaque véhicule actif.
     * Contrat front : { success, distance_km, duration_min, polyline, vehicles }.
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

        $pickupPid = trim((string) ($validated['pickup_place_id'] ?? ''));
        $dropoffPid = trim((string) ($validated['dropoff_place_id'] ?? ''));

        $context = [
            'ride_date' => $validated['ride_date'] ?? '',
            'ride_time' => $validated['ride_time'] ?? '',
        ];

        // Départ = arrivée : devis à distance nulle, centré sur le lieu si connu.
        $samePid = $pickupPid !== '' && $pickupPid === $dropoffPid;
        $sameAddr = ! $samePid
            && strtolower(trim($validated['pickup_address'])) === strtolower(trim($validated['dropoff_address']));

        if ($samePid || $sameAddr) {
            $loc = null;
            if ($pickupPid !== '') {
                try {
                    $loc = GoogleMaps::placeLocation($pickupPid);
                } catch (BookingException) {
                    $loc = null;
                }
            }

            return response()->json([
                'success' => true,
                'distance_km' => 0,
                'duration_min' => 0,
                'polyline' => '',
                'lat' => $loc['lat'] ?? 0,
                'lng' => $loc['lng'] ?? 0,
                'vehicles' => Pricing::quotesForAllVehicles(0, 0, $context),
            ]);
        }

        $route = GoogleMaps::computeRoute(
            $validated['pickup_address'],
            $validated['dropoff_address'],
            $pickupPid,
            $dropoffPid
        );

        return response()->json([
            'success' => true,
            'distance_km' => $route['distance_km'],
            'duration_min' => $route['duration_min'],
            'polyline' => $route['polyline'],
            'vehicles' => Pricing::quotesForAllVehicles($route['distance_km'], $route['duration_min'], $context),
        ]);
    }
}
