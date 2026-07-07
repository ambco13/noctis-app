<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleMaps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlacesController extends Controller
{
    /**
     * Proxy d'autocomplétion d'adresses (la clé Google reste côté serveur).
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'input' => ['required', 'string', 'max:200'],
        ]);

        return response()->json([
            'suggestions' => GoogleMaps::autocomplete($validated['input']),
        ]);
    }
}
