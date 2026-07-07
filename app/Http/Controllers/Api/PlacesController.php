<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Services\GoogleMaps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlacesController extends Controller
{
    /**
     * Proxy d'autocomplétion d'adresses (la clé Google reste côté serveur).
     * Contrat front : { predictions: [{ id, main, secondary, full }] } — jamais d'erreur.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');

        try {
            $predictions = GoogleMaps::autocomplete($q);
        } catch (BookingException) {
            $predictions = [];
        }

        return response()->json(['predictions' => $predictions]);
    }
}
