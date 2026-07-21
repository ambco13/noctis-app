<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Site vitrine public (marketing) : accueil, pages service, flotte, à propos,
 * contact. Registre visuel partagé (dark navy .ntb-scope) mais layout, header
 * et footer propres (layouts.marketing).
 *
 * L'accueil embarque le VRAI formulaire de réservation (partial booking._hero)
 * + son runtime JS : autocomplétion, pickers et soumission passent par le
 * tunnel existant, sans le dupliquer ni le modifier.
 *
 * Contenu dans config/marketing.php.
 */
class MarketingController extends Controller
{
    /** Trajet vide pour le hero (même forme que BookingPageController::EMPTY_STEP1). */
    private const EMPTY_STEP1 = [
        'pickup_address' => '',
        'dropoff_address' => '',
        'ride_date' => '',
        'ride_time' => '',
        'pickup_place_id' => '',
        'dropoff_place_id' => '',
    ];

    public function home(Request $request): View
    {
        // Le hero réutilise la session étape 1 si elle existe (retour depuis le
        // tunnel), sinon un trajet vide — même logique que la page /reservation.
        $prefill = array_merge(self::EMPTY_STEP1, (array) $request->session()->get('booking_step1', []));

        return view('marketing.home', [
            'prefill' => $prefill,
            'services' => config('marketing.services'),
            'competences' => config('marketing.competences'),
            'whyChoose' => config('marketing.why_choose'),
        ]);
    }

    public function service(string $slug): View
    {
        $services = config('marketing.services');
        abort_unless(isset($services[$slug]), 404);

        return view('marketing.service', [
            'slug' => $slug,
            'service' => $services[$slug],
            'services' => $services,
        ]);
    }

    public function fleet(): View
    {
        return view('marketing.fleet', [
            'services' => config('marketing.services'),
            'fleet' => config('marketing.fleet'),
        ]);
    }

    public function about(): View
    {
        return view('marketing.about', [
            'services' => config('marketing.services'),
            'competences' => config('marketing.competences'),
            'whyChoose' => config('marketing.why_choose'),
        ]);
    }

    public function contact(): View
    {
        return view('marketing.contact', [
            'services' => config('marketing.services'),
            'contact' => config('marketing.contact'),
        ]);
    }

    /**
     * Réception du formulaire de contact. Volontairement minimal : validation
     * + message de confirmation, sans envoi d'email (aucun canal mail dédié au
     * contact vitrine n'est encore configuré). À brancher sur un Mailable une
     * fois l'adresse de réception fournie par le client.
     */
    public function contactSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        return redirect()
            ->route('marketing.contact')
            ->with('status', 'Thank you. Your message has been received — we will get back to you shortly.');
    }
}
