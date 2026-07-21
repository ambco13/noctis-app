<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-ntb-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    {{-- Polices de la marque (partagées avec le site vitrine). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Marcellus&family=Albert+Sans:wght@300;400;500;600;700&family=Spline+Sans+Mono:wght@500;600&display=swap">

    {{-- CSS du tunnel (formulaire + pickers) — le hero garde sa logique. --}}
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}?v={{ filemtime(public_path('assets/css/ntb-public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-pickers.css') }}?v={{ filemtime(public_path('assets/css/ntb-pickers.css')) }}">
    {{-- Header du site vitrine + habillage « nouveau design » du hero. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-marketing.css') }}?v={{ filemtime(public_path('assets/css/ntb-marketing.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-booking-hero.css') }}?v={{ filemtime(public_path('assets/css/ntb-booking-hero.css')) }}">
    @stack('styles')
    {!! \App\Support\Design::styleBlock() !!}
    <style>
        html, body { max-width: 100%; overflow-x: clip; margin: 0; padding: 0; }
        /* Le hero va jusqu'aux bords ; le header vitrine se pose au-dessus. */
        body > main { padding: 0; }
        /* Hero page 1 : masque la barre de défilement (le calendrier flatpickr,
           injecté dans <body>, dépasse sinon). Défilement toujours fonctionnel. */
        html:has(.ntb-home), body:has(.ntb-home) { scrollbar-width: none; }
        html:has(.ntb-home)::-webkit-scrollbar,
        body:has(.ntb-home)::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>
    <div class="mkt ntb-theme-light">
        @include('marketing.partials.header')
    </div>
    <main>
        @yield('content')
    </main>

    @include('partials.booking-runtime')
    @stack('scripts')
</body>
</html>
