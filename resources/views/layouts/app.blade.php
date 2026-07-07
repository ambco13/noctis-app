<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-pickers.css') }}">
    @stack('styles')
    {!! \App\Support\Design::styleBlock() !!}
</head>
<body>
    <main>
        @yield('content')
    </main>

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
        <script src="https://js.stripe.com/v3/"></script>
    @endif
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/fr.js') }}"></script>
    <script src="{{ asset('vendor/leaflet/leaflet.min.js') }}"></script>
    <script src="{{ asset('assets/js/ntb-datepicker.js') }}"></script>
    <script src="{{ asset('assets/js/ntb-public.js') }}"></script>
    @stack('scripts')
</body>
</html>