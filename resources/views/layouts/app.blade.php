<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}">
    {{-- ?v=mtime : casse le cache navigateur à chaque modif du CSS, sans quoi
         un simple F5 peut reservir une version obsolète du fichier. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}?v={{ filemtime(public_path('assets/css/ntb-public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-pickers.css') }}?v={{ filemtime(public_path('assets/css/ntb-pickers.css')) }}">
    @stack('styles')
    {!! \App\Support\Design::styleBlock() !!}
    <style>
        .ntb-topnav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding: 14px 24px; box-sizing: border-box;
            background: var(--ntb-surf); border-bottom: 1px solid var(--ntb-line);
            max-width: 100vw;
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        }
        .ntb-topnav-brand {
            font-family: var(--ntb-serif); font-size: 19px; letter-spacing: .01em;
            color: var(--ntb-text); text-decoration: none;
            flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .ntb-topnav-links { display: flex; align-items: center; gap: 20px; min-width: 0; flex: 0 1 auto; }
        .ntb-topnav-links a {
            font-family: var(--ntb-sans); font-size: 14px; font-weight: 600;
            color: var(--ntb-muted); text-decoration: none; transition: color 180ms;
            /* Un email n'a pas d'espace où retourner à la ligne : on tronque
               plutôt que de laisser le texte pousser la barre hors écran. */
            max-width: 45vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .ntb-topnav-links a:hover { color: var(--ntb-accent-hi); }
        .ntb-topnav-account {
            max-width: none !important; flex: 0 0 auto;
            display: flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--ntb-surf2); color: var(--ntb-muted);
        }
        .ntb-topnav-account:hover { color: var(--ntb-accent-hi); background: var(--ntb-accent-soft); }

        /* Filet de sécurité : un contenu imprévu (texte long, image...) ne doit
           jamais créer de scroll horizontal sur la page.
           `clip` et non `hidden` : `overflow-x: hidden` transforme body en
           scroll container (overflow-y calculé en `auto`), ce qui casse tout
           `position: sticky` descendant — le sticky se cale alors sur la
           scrollport de body, qui ne défile jamais. `clip` coupe le
           débordement sans créer de scroll container. */
        html, body { max-width: 100%; overflow-x: clip; }

        /* Marge latérale globale du site : 24px en mobile, 48px à partir du desktop.
           Un peu d'espace en haut, beaucoup plus en bas (sous le formulaire). */
        body > main { padding: 55px 24px 20px; box-sizing: border-box; }
        @media (min-width: 1025px) {
            .ntb-topnav { padding-left: 48px; padding-right: 48px; }
            /* max-width = 3 cartes véhicule (269px×3) + 2 gaps (16px×2) + paddings
               carrousel (~7px) + padding colonne gauche (48px) + gap grille vers
               l'aside (24px) + aside (440px) + padding main (48px×2) = 1454px.
               Au-delà, l'espace en trop devient du padding automatique (centré). */
            body > main { max-width: 1454px; margin-left: auto; margin-right: auto; padding-left: 48px; padding-right: 48px; }
        }
    </style>
</head>
<body>
    <header class="ntb-topnav">
        <a class="ntb-topnav-brand" href="{{ route('booking.form', ['new' => 1]) }}">{{ config('app.name') }}</a>
        <nav class="ntb-topnav-links">
            <a href="{{ route('booking.form', ['new' => 1]) }}">{{ __('Réserver') }}</a>
            <a href="{{ route('account') }}" class="ntb-topnav-account"
                title="{{ auth()->check() ? auth()->user()->email : __('Mon compte') }}"
                aria-label="{{ __('Mon compte') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                </svg>
            </a>
        </nav>
    </header>
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
    <script src="{{ asset('assets/js/ntb-datepicker.js') }}?v={{ filemtime(public_path('assets/js/ntb-datepicker.js')) }}"></script>
    <script src="{{ asset('assets/js/ntb-public.js') }}?v={{ filemtime(public_path('assets/js/ntb-public.js')) }}"></script>
    @stack('scripts')
</body>
</html>