<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    {{-- No-FOUC : pose data-ntb-theme sur <html> AVANT le premier rendu.
         Mode forcé (dark/light) → appliqué tel quel ; auto → localStorage
         ('ntb_theme') puis préférence système. Tout le thème clair du CSS
         est conditionné à cet attribut. --}}
    <script id="ntb2-theme-init">
    (function () {
        var a = @json(\App\Support\Design::themeMode());
        var m;
        if (a === 'auto') {
            var s = null;
            try { s = localStorage.getItem('ntb_theme'); } catch (e) {}
            s = s || 'auto';
            m = s === 'auto' ? (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark') : s;
        } else { m = a; }
        document.documentElement.setAttribute('data-ntb-theme', m);
    })();
    </script>

    {{-- Polices de la marque (partagées avec le site vitrine : header commun). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Marcellus&family=Albert+Sans:wght@300;400;500;600;700&family=Spline+Sans+Mono:wght@500;600&display=swap">

    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}">
    {{-- ?v=mtime : casse le cache navigateur à chaque modif du CSS, sans quoi
         un simple F5 peut reservir une version obsolète du fichier. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}?v={{ filemtime(public_path('assets/css/ntb-public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-pickers.css') }}?v={{ filemtime(public_path('assets/css/ntb-pickers.css')) }}">
    {{-- Header commun à tout le site (nav vitrine, aussi sur étapes 2-4 + compte). --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-marketing.css') }}?v={{ filemtime(public_path('assets/css/ntb-marketing.css')) }}">
    @stack('styles')
    {!! \App\Support\Design::styleBlock() !!}
    <style>
        /* Filet de sécurité : un contenu imprévu (texte long, image...) ne doit
           jamais créer de scroll horizontal sur la page.
           `clip` et non `hidden` : `overflow-x: hidden` transforme body en
           scroll container (overflow-y calculé en `auto`), ce qui casse tout
           `position: sticky` descendant — le sticky se cale alors sur la
           scrollport de body, qui ne défile jamais. `clip` coupe le
           débordement sans créer de scroll container. */
        html, body { max-width: 100%; overflow-x: clip; margin: 0; padding: 0; }

        /* Marge latérale globale du site : 24px en mobile, 48px à partir du desktop.
           Le header vitrine (sticky, 60px) occupe déjà le flux en haut : un
           petit padding suffit sous lui. */
        body > main { padding: 24px 24px 20px; box-sizing: border-box; }
        @media (min-width: 1025px) {
            .ntb-topnav { padding-left: 48px; padding-right: 48px; }
            /* max-width = 3 cartes véhicule (269px×3) + 2 gaps (16px×2) + paddings
               carrousel (~7px) + padding colonne gauche (48px) + gap grille vers
               l'aside (24px) + aside (440px) + padding main (48px×2) = 1454px.
               Au-delà, l'espace en trop devient du padding automatique (centré). */
            body > main { max-width: 1454px; margin-left: auto; margin-right: auto; padding-left: 48px; padding-right: 48px; }
        }
        /* Page 1 (hero) : l'image de fond doit aller jusqu'aux bords du
           navigateur, donc pas de padding/max-width sur main ici -- l'espace
           autour du formulaire vient du margin de .ntb-hero-inner à la place. */
        body > main:has(.ntb-home) { padding: 0; max-width: none; margin: 0; }
    </style>
</head>
<body>
    {{-- Header commun du site (nav vitrine) — même sur les étapes 2-4 et le compte. --}}
    <div class="mkt ntb-theme-light">
        @include('marketing.partials.header')
    </div>
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
    {{-- Comportements du header vitrine (burger mobile, menu Services). --}}
    <script src="{{ asset('assets/js/ntb-marketing.js') }}?v={{ filemtime(public_path('assets/js/ntb-marketing.js')) }}"></script>
    @stack('scripts')
</body>
</html>