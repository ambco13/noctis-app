<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Noctis — Private Chauffeur · Paris & Europe')</title>

    {{-- Polices de la marque — hébergées en local (pas de hotlink Google :
         CSP stricte respectée + aucune fuite d'IP vers Google / RGPD). --}}
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}?v={{ filemtime(public_path('assets/css/fonts.css')) }}">

    <link rel="stylesheet" href="{{ asset('assets/css/ntb-marketing.css') }}?v={{ filemtime(public_path('assets/css/ntb-marketing.css')) }}">
    @stack('styles')
    <style>html,body{margin:0;padding:0;max-width:100%;overflow-x:clip}</style>
</head>
<body>
    {{-- Nouveau design : registre CLAIR (.ntb-theme-light) + classe .mkt. --}}
    <div class="mkt ntb-theme-light" style="font-family:var(--font-sans);background:var(--ntb-bg)">
        @include('marketing.partials.header')
        <main>
            @yield('content')
        </main>
        @include('marketing.partials.footer')
    </div>
    <script src="{{ asset('assets/js/ntb-marketing.js') }}?v={{ filemtime(public_path('assets/js/ntb-marketing.js')) }}"></script>
    @stack('scripts')
</body>
</html>
