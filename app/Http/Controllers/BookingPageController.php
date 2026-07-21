<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages du tunnel de réservation.
 * Étape 1 (/reservation) : formulaire de trajet, POST → session → redirection.
 * Étapes 2-4 (/ma-reservation) : SPA pilotée par ntb-public.js.
 */
class BookingPageController extends Controller
{
    public const SESSION_STEP1 = 'booking_step1';

    public const EMPTY_STEP1 = [
        'pickup_address' => '',
        'dropoff_address' => '',
        'ride_date' => '',
        'ride_time' => '',
        'pickup_place_id' => '',
        'dropoff_place_id' => '',
    ];

    /**
     * L'étape 1 vit désormais dans le hero de la home (/) : cette route ne fait
     * que rediriger vers l'accueil, en réinitialisant le tunnel si ?new=1
     * (« Nouvelle réservation »). Conserve la compat des anciens liens.
     */
    public function showForm(Request $request): RedirectResponse
    {
        if ($request->query('new')) {
            $request->session()->forget(self::SESSION_STEP1);
        }

        return redirect('/');
    }

    public function submitStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'NTB2_pickup' => ['required', 'string', 'max:500'],
            'NTB2_dropoff' => ['required', 'string', 'max:500'],
            'NTB2_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'NTB2_time' => ['required', 'date_format:H:i'],
            'NTB2_pickup_place_id' => ['nullable', 'string', 'max:255'],
            'NTB2_dropoff_place_id' => ['nullable', 'string', 'max:255'],
        ]);

        session([self::SESSION_STEP1 => [
            'pickup_address' => $validated['NTB2_pickup'],
            'dropoff_address' => $validated['NTB2_dropoff'],
            'ride_date' => $validated['NTB2_date'],
            'ride_time' => $validated['NTB2_time'],
            'pickup_place_id' => $validated['NTB2_pickup_place_id'] ?? '',
            'dropoff_place_id' => $validated['NTB2_dropoff_place_id'] ?? '',
        ]]);

        return redirect()->route('booking.steps');
    }

    public function showSteps(): View
    {
        return view('booking.steps', [
            'step1' => array_merge(self::EMPTY_STEP1, (array) session(self::SESSION_STEP1, [])),
        ]);
    }
}
