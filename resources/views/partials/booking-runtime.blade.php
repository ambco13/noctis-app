{{-- Bootstrap front du tunnel de réservation (config NTB2_DATA + scripts).
     Extrait de layouts/app.blade.php pour être réutilisable tel quel par
     l'accueil du site vitrine, qui embarque le vrai formulaire hero.
     Un seul point de vérité : ne pas dupliquer NTB2_DATA ailleurs. --}}
<script>
    // Configuration du front (équivalent du wp_localize_script du plugin d'origine).
    window.NTB2_DATA = {
        restUrl: @json(url('/api/v1')),
        nonce: @json(csrf_token()),
        currencySymbol: @json(\App\Support\Settings::currencySymbol()),
        currency: @json(\App\Support\Settings::currency()),
        bookingUrl: @json(route('booking.steps')),
        homeUrl: @json(url('/')),
        hasStripe: @json(\App\Support\Secrets::stripeKey() !== ''),
        stripeKey: @json(\App\Support\Secrets::stripeKey()),
        hasPaypal: @json(\App\Support\Secrets::paypalClientId() !== ''),
        paypalLoaded: @json(\App\Support\Secrets::paypalClientId() !== ''),
        asideDetachBody: @json((string) \App\Support\Settings::get('aside_detach_body', '0') === '1'),
        currentUser: @json(auth()->check() ? ['name' => auth()->user()->name, 'email' => auth()->user()->email] : null),
        i18n: {
            computing: @json(__('Calcul de votre trajet…')),
            noVehicles: @json(__('Aucun véhicule disponible pour ce trajet.')),
            routeError: @json(__('Impossible de calculer le trajet. Vérifiez les adresses.')),
            selectVehicle: @json(__('Veuillez sélectionner un véhicule.')),
            paymentError: @json(__('Le paiement a échoué. Veuillez réessayer.')),
            processing: @json(__('Traitement en cours…')),
            required: @json(__('Ce champ est requis.')),
            invalidEmail: @json(__('Adresse email invalide.')),
            kmUnit: @json(__('km')),
            minUnit: @json(__('min')),
            distance: @json(__('Distance')),
            duration: @json(__('Durée estimée')),
            confirmTitle: @json(__('Réservation confirmée')),
            payNow: @json(__('Payer maintenant')),
            bookNow: @json(__('Réserver')),
            savedCards: @json(__('Cartes enregistrées')),
            newCard: @json(__('Nouvelle carte')),
            saveCard: @json(__('Enregistrer cette carte pour mes prochaines réservations')),
            cardDeclined: @json(__('Votre carte a été refusée. Essayez une autre carte.'))
        }
    };
</script>
@if (\App\Support\Secrets::stripeKey() !== '')
    {{-- async (et non defer) : Stripe ne sert qu'à l'étape paiement, mais
         placé avant les scripts locaux il bloquait leur exécution — sur un
         réseau lent, les pickers de la page 1 attendaient js.stripe.com
         plusieurs secondes. defer ne suffirait pas : les scripts locaux
         s'initialisent sur DOMContentLoaded, que defer retarde aussi.
         Si le paiement est atteint avant l'arrivée du script, le JS
         l'attend (waitForStripe dans ntb-public.js). --}}
    <script async src="https://js.stripe.com/v3/"></script>
@endif
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/fr.js') }}"></script>
<script src="{{ asset('vendor/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/ntb-datepicker.js') }}?v={{ filemtime(public_path('assets/js/ntb-datepicker.js')) }}"></script>
<script src="{{ asset('assets/js/ntb-public.js') }}?v={{ filemtime(public_path('assets/js/ntb-public.js')) }}"></script>
