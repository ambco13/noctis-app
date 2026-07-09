@extends('layouts.app')

@section('title', __('Mon compte'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-dashboard.css') }}">
@endpush

@section('content')
<div class="ntb-dashboard">
    @auth
        @php
            $user = auth()->user();
            $initials = strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1));
            if ($initials === '') {
                $initials = strtoupper(mb_substr($user->email, 0, 2));
            }
        @endphp

        <nav class="ntb-topbar" aria-label="{{ __('Navigation du compte') }}">
            <div class="ntb-topbar-left">
                <a href="{{ route('account', ['tab' => 'bookings']) }}"
                   class="ntb-tab-link {{ $tab === 'bookings' ? 'active' : '' }}"
                   @if ($tab === 'bookings') aria-current="page" @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M16 3v4M8 3v4M4 11h16"/></svg>
                    <span class="ntb-topbar-label">{{ __('Mes réservations') }}</span>
                </a>
                <div class="ntb-tab-divider" aria-hidden="true"></div>
                <a href="{{ route('account', ['tab' => 'profile']) }}"
                   class="ntb-tab-link {{ $tab === 'profile' ? 'active' : '' }}"
                   @if ($tab === 'profile') aria-current="page" @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                    <span class="ntb-topbar-label">{{ __('Mon profil') }}</span>
                </a>
            </div>
            <div class="ntb-topbar-right">
                <div class="ntb-avatar" aria-hidden="true">{{ $initials }}</div>
                <a href="#" id="ntb-logout-btn" class="ntb-logout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/><path d="M7 12h14l-3-3m0 6 3-3"/></svg>
                    <span class="ntb-topbar-label">{{ __('Déconnexion') }}</span>
                </a>
            </div>
        </nav>

        <div class="ntb-content">
            @if ($tab === 'bookings')
                @include('account.bookings')
            @else
                @include('account.profile')
            @endif
        </div>

        <script>
        // NTB2_DATA n'est défini que plus bas dans layouts/app.blade.php (après
        // @yield('content')) : on attend DOMContentLoaded pour être sûr qu'il
        // existe déjà, sinon ce script (exécuté immédiatement au parsing) ne
        // voit jamais la variable et n'attache jamais le listener.
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('ntb-logout-btn');
            if (!btn || !window.NTB2_DATA) return;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                fetch(NTB2_DATA.restUrl + '/auth/logout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': NTB2_DATA.nonce }
                }).finally(function () { window.location.href = NTB2_DATA.homeUrl; });
            });
        });
        </script>
    @else
        @include('account.auth')
    @endauth
</div>
@endsection
