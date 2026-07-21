{{-- Feuilles de style du tunnel (pickers + carte). Extrait de layouts/app
     pour réutilisation par l'accueil du site vitrine. ntb-public.css reste
     chargé par chaque layout (tokens partagés). --}}
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/ntb-pickers.css') }}?v={{ filemtime(public_path('assets/css/ntb-pickers.css')) }}">
