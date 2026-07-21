<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Site vitrine public (marketing) — nouveau design Noctis (registre clair,
 * éditorial). Accueil, pages service, flotte, à propos, contact.
 *
 * Distinct du tunnel de réservation : tous les CTA (« Get a Quote »,
 * « Estimate my ride ») renvoient vers /reservation, qui reste inchangé.
 * Contenu dans config/marketing.php.
 */
class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home');
    }

    public function service(string $slug): View
    {
        $services = config('marketing.services');
        abort_unless(isset($services[$slug]), 404);

        return view('marketing.service', [
            'slug' => $slug,
            'service' => $services[$slug],
        ]);
    }

    public function fleet(): View
    {
        return view('marketing.fleet');
    }

    public function about(): View
    {
        return view('marketing.about');
    }

    public function contact(): View
    {
        return view('marketing.contact');
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
