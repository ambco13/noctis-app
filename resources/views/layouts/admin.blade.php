<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-admin.css') }}">
    <style>
        /* Coquille remplaçant le chrome de l'admin WordPress. */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #1f2937; }
        .nadm-shell { display: flex; min-height: 100vh; }
        .nadm-side { width: 220px; background: #0a0c12; color: #e5e7eb; padding: 20px 0; flex-shrink: 0; }
        .nadm-brand { font-family: Georgia, serif; letter-spacing: 4px; text-transform: uppercase; text-align: center; color: #fff; margin: 0 0 24px; font-size: 18px; }
        .nadm-nav a { display: block; padding: 10px 22px; color: #9ca3af; text-decoration: none; font-size: 14px; }
        .nadm-nav a:hover { color: #fff; background: rgba(255,255,255,.05); }
        .nadm-nav a.active { color: #fff; background: rgba(255,255,255,.09); border-left: 3px solid #4d8bc4; padding-left: 19px; }
        .nadm-main { flex: 1; padding: 28px 32px; max-width: 1200px; }
        .nadm-foot { padding: 18px 22px; font-size: 12px; color: #6b7280; }
        .nadm-foot a { color: #9ca3af; }
        .nadm-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; }
        .nadm-errors { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; }
        table.nadm-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
        table.nadm-table th, table.nadm-table td { padding: 10px 12px; text-align: left; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
        table.nadm-table th { background: #f8fafc; color: #64748b; font-weight: 600; }
        .nadm-form { background: #fff; border-radius: 10px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,.07); max-width: 720px; }
        .nadm-form label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 4px; }
        .nadm-form input[type=text], .nadm-form input[type=email], .nadm-form input[type=number],
        .nadm-form input[type=time], .nadm-form textarea, .nadm-form select {
            width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;
        }
        .nadm-btn { display: inline-block; background: #111827; color: #fff; border: 0; padding: 9px 18px; border-radius: 7px; font-size: 14px; cursor: pointer; text-decoration: none; }
        .nadm-btn:hover { background: #374151; }
        .nadm-btn--danger { background: #b91c1c; }
        .nadm-btn--light { background: #e5e7eb; color: #111827; }
        .nadm-badge { display: inline-block; padding: 2px 9px; border-radius: 99px; font-size: 12px; font-weight: 600; }
        .nadm-badge--pending { background: #fef9c3; color: #854d0e; }
        .nadm-badge--confirmed { background: #dcfce7; color: #166534; }
        .nadm-badge--completed { background: #e0e7ff; color: #3730a3; }
        .nadm-badge--cancelled { background: #f3f4f6; color: #6b7280; }
        .nadm-badge--failed { background: #fee2e2; color: #991b1b; }
        .nadm-inline { display: inline; }
    </style>
</head>
<body>
<div class="nadm-shell">
    <aside class="nadm-side">
        <h1 class="nadm-brand">Noctis</h1>
        <nav class="nadm-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">{{ __('Tableau de bord') }}</a>
            <a href="{{ route('admin.bookings') }}" class="{{ request()->routeIs('admin.bookings*') ? 'active' : '' }}">{{ __('Réservations') }}</a>
            <a href="{{ route('admin.customers') }}" class="{{ request()->routeIs('admin.customers') ? 'active' : '' }}">{{ __('Clients') }}</a>
            <a href="{{ route('admin.vehicles') }}" class="{{ request()->routeIs('admin.vehicles*') ? 'active' : '' }}">{{ __('Véhicules') }}</a>
            <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">{{ __('Réglages') }}</a>
        </nav>
        <div class="nadm-foot">
            {{ auth()->user()->email }}<br>
            <a href="{{ url('/') }}">{{ __('Voir le site') }}</a>
        </div>
    </aside>
    <main class="nadm-main ntb-admin">
        @if (session('ok'))
            <div class="nadm-ok">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="nadm-errors">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>