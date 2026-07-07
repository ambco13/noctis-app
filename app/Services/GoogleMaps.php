<?php

namespace App\Services;

use App\Exceptions\BookingException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client Google Maps Platform.
 * - Routes API (computeRoutes) pour distance/durée.
 * - Places Autocomplete + Place Details (proxy serveur pour protéger la clé).
 *
 * Tous les appels sont faits côté serveur : la clé (config/services.php,
 * GOOGLE_MAPS_API_KEY dans .env) n'est jamais exposée au navigateur.
 */
class GoogleMaps
{
    public const ROUTES_ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public const PLACES_AUTOCOMPLETE = 'https://places.googleapis.com/v1/places:autocomplete';

    public const PLACE_DETAILS = 'https://places.googleapis.com/v1/places/';

    private static function apiKey(): string
    {
        return trim((string) config('services.google_maps.key'));
    }

    /**
     * Calcule distance (km) et durée (min) entre deux adresses ou place IDs.
     * Résultat en cache 12 h pour limiter les coûts.
     *
     * @return array{distance_km: float, duration_min: float, origin: string, destination: string, polyline: string}
     *
     * @throws BookingException
     */
    public static function computeRoute(string $origin, string $destination, string $originPlaceId = '', string $destPlaceId = ''): array
    {
        $key = self::apiKey();
        if ($key === '') {
            throw new BookingException(__('Clé API Google manquante.'));
        }

        $originPlaceId = trim($originPlaceId);
        $destPlaceId = trim($destPlaceId);
        $origin = trim(strip_tags($origin));
        $destination = trim(strip_tags($destination));

        if ($origin === '' || $destination === '') {
            throw new BookingException(__("Adresses de départ et d'arrivée requises."));
        }

        // Clé de cache : préfère les place IDs (plus précis), sinon les adresses texte.
        $cacheSeed = ($originPlaceId !== '' ? $originPlaceId : $origin)
            .'|'.($destPlaceId !== '' ? $destPlaceId : $destination);
        $cacheKey = 'route_v2_'.md5(strtolower($cacheSeed));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $body = [
            'origin' => $originPlaceId !== '' ? ['placeId' => $originPlaceId] : ['address' => $origin],
            'destination' => $destPlaceId !== '' ? ['placeId' => $destPlaceId] : ['address' => $destination],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'units' => 'METRIC',
        ];

        $response = Http::timeout(15)
            ->withHeaders([
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline',
            ])
            ->post(self::ROUTES_ENDPOINT, $body);

        $data = $response->json();

        if (! $response->ok() || empty($data['routes'][0])) {
            throw new BookingException($data['error']['message'] ?? __('Trajet introuvable.'));
        }

        $route = $data['routes'][0];
        $meters = (int) ($route['distanceMeters'] ?? 0);
        // "duration" est une chaîne style "1234s".
        $seconds = (int) rtrim((string) ($route['duration'] ?? '0'), 's');

        $result = [
            'distance_km' => round($meters / 1000, 2),
            'duration_min' => round($seconds / 60, 1),
            'origin' => $origin,
            'destination' => $destination,
            'polyline' => (string) ($route['polyline']['encodedPolyline'] ?? ''),
        ];

        // Cache 12 h : un trajet identique coûte 1 seul appel par demi-journée.
        Cache::put($cacheKey, $result, now()->addHours(12));

        return $result;
    }

    /**
     * Proxy d'autocomplétion d'adresses (Places API new).
     *
     * @return array<int, array{id: string, main: string, secondary: string, full: string}>
     *
     * @throws BookingException
     */
    public static function autocomplete(string $input): array
    {
        $key = self::apiKey();
        if ($key === '') {
            throw new BookingException(__('Clé API Google manquante.'));
        }

        $input = trim(strip_tags($input));
        if (mb_strlen($input) < 3) {
            return [];
        }

        $response = Http::timeout(12)
            ->withHeaders(['X-Goog-Api-Key' => $key])
            ->post(self::PLACES_AUTOCOMPLETE, [
                'input' => $input,
                'languageCode' => 'fr',
            ]);

        $suggestions = [];

        foreach ($response->json('suggestions', []) as $s) {
            if (empty($s['placePrediction'])) {
                continue;
            }
            $p = $s['placePrediction'];
            $suggestions[] = [
                'id' => $p['placeId'] ?? '',
                'main' => $p['structuredFormat']['mainText']['text'] ?? ($p['text']['text'] ?? ''),
                'secondary' => $p['structuredFormat']['secondaryText']['text'] ?? '',
                'full' => $p['text']['text'] ?? '',
            ];
        }

        return $suggestions;
    }

    /**
     * Localisation (lat/lng) d'un place_id via Place Details.
     *
     * @return array{lat: float, lng: float}
     *
     * @throws BookingException
     */
    public static function placeLocation(string $placeId): array
    {
        $key = self::apiKey();
        if ($key === '') {
            throw new BookingException(__('Clé API Google manquante.'));
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'location',
            ])
            ->get(self::PLACE_DETAILS.rawurlencode($placeId));

        $location = $response->json('location');
        if (empty($location['latitude'])) {
            throw new BookingException(__('Lieu introuvable.'));
        }

        return [
            'lat' => (float) $location['latitude'],
            'lng' => (float) $location['longitude'],
        ];
    }
}
