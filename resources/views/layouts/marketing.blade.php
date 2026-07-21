<!DOCTYPE html>
<html lang="en" data-ntb-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Noctis — Private Chauffeur · Paris & Europe')</title>

    {{-- Polices de la marque (Marcellus / Albert Sans / Spline Sans Mono),
         chargées sur le site vitrine. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Marcellus&family=Albert+Sans:wght@300;400;500;600;700&family=Spline+Sans+Mono:wght@500;600&display=swap">

    {{-- Tokens --ntb-* partagés (.ntb-scope) + preset d'accent admin + styles vitrine. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}?v={{ filemtime(public_path('assets/css/ntb-public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-marketing.css') }}?v={{ filemtime(public_path('assets/css/ntb-marketing.css')) }}">
    @stack('styles')
    {!! \App\Support\Design::styleBlock() !!}
    <style>
        html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: clip; background: var(--ntb-bg, #16181d); }
    </style>
</head>
<body class="ntb-mk-page">
    <div class="ntb-scope ntb-market">
        @include('marketing.partials.header')
        <main>
            @yield('content')
        </main>
        @include('marketing.partials.footer')
    </div>
    @stack('scripts')
</body>
</html>
