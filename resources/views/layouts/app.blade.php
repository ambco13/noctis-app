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

    {{-- ?v=mtime : casse le cache navigateur à chaque modif du CSS, sans quoi
         un simple F5 peut reservir une version obsolète du fichier. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}?v={{ filemtime(public_path('assets/css/ntb-public.css')) }}">
    @include('partials.booking-styles')
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
        /* Page hero (page 1) : nav transparente par-dessus l'image plein écran. */
        body:has(.ntb-home) .ntb-topnav { background: transparent; border-bottom: none; }
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
        /* Texte blanc par-dessus l'image sombre du hero. */
        body:has(.ntb-home) .ntb-topnav-brand,
        body:has(.ntb-home) .ntb-topnav-links a { color: #ffffff; }
        body:has(.ntb-home) .ntb-topnav-account { background: rgba(255,255,255,.15); color: #ffffff; }

        /* Filet de sécurité : un contenu imprévu (texte long, image...) ne doit
           jamais créer de scroll horizontal sur la page.
           `clip` et non `hidden` : `overflow-x: hidden` transforme body en
           scroll container (overflow-y calculé en `auto`), ce qui casse tout
           `position: sticky` descendant — le sticky se cale alors sur la
           scrollport de body, qui ne défile jamais. `clip` coupe le
           débordement sans créer de scroll container. */
        html, body { max-width: 100%; overflow-x: clip; margin: 0; padding: 0; }

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
        /* Page 1 (hero) : l'image de fond doit aller jusqu'aux bords du
           navigateur, donc pas de padding/max-width sur main ici -- l'espace
           autour du formulaire vient du margin de .ntb-hero-inner à la place. */
        body > main:has(.ntb-home) { padding: 0; max-width: none; margin: 0; }
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

    @include('partials.booking-runtime')
    @stack('scripts')
</body>
</html>