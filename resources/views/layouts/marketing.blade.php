<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Noctis — Private Chauffeur · Paris & Europe')</title>

    {{-- Polices de la marque. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Marcellus&family=Albert+Sans:wght@300;400;500;600;700&family=Spline+Sans+Mono:wght@500;600&display=swap">

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
