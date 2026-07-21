@php($navServices = config('marketing.services'))
<header class="ntb-mk-header">
    <a href="{{ route('marketing.home') }}" class="ntb-mk-brand" aria-label="Noctis — home">
        <span class="ntb-mk-brand-dot" aria-hidden="true"></span>
        NOCTIS <span class="ntb-mk-brand-sub">Chauffeur privé</span>
    </a>

    <nav class="ntb-mk-nav" aria-label="Primary">
        <a class="ntb-mk-nav-link {{ request()->routeIs('marketing.home') ? 'is-active' : '' }}"
           href="{{ route('marketing.home') }}">Home</a>

        <span class="ntb-mk-drop">
            <a class="ntb-mk-nav-link ntb-mk-drop-toggle {{ request()->routeIs('marketing.service') ? 'is-active' : '' }}"
               href="{{ route('marketing.service', ['slug' => array_key_first($navServices)]) }}"
               aria-haspopup="true">Services</a>
            <span class="ntb-mk-drop-menu" role="menu">
                @foreach ($navServices as $slug => $svc)
                    <a role="menuitem" href="{{ route('marketing.service', ['slug' => $slug]) }}">{{ $svc['nav'] }}</a>
                @endforeach
            </span>
        </span>

        <a class="ntb-mk-nav-link {{ request()->routeIs('marketing.fleet') ? 'is-active' : '' }}"
           href="{{ route('marketing.fleet') }}">Fleet</a>
        <a class="ntb-mk-nav-link {{ request()->routeIs('marketing.about') ? 'is-active' : '' }}"
           href="{{ route('marketing.about') }}">About</a>
        <a class="ntb-mk-nav-link {{ request()->routeIs('marketing.contact') ? 'is-active' : '' }}"
           href="{{ route('marketing.contact') }}">Contact</a>

        <a href="{{ route('account') }}" class="ntb-mk-account" aria-label="Mon compte"
           title="{{ auth()->check() ? auth()->user()->email : 'Mon compte' }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
            </svg>
        </a>

        <a href="{{ route('booking.form', ['new' => 1]) }}" class="ntb-mk-btn ntb-mk-btn--sm ntb-mk-hide-sm">Get a Quote</a>
    </nav>
</header>
