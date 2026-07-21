@php($navServices = config('marketing.services'))
@php($isSvc = request()->routeIs('marketing.service'))
@php($heroNav = request()->routeIs('marketing.home'))
<header id="mkt-header" @if ($heroNav) data-hero-nav @endif style="position:sticky;top:0;z-index:40;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 44px;height:60px;background:color-mix(in oklch, var(--ntb-bg) 72%, transparent);backdrop-filter:blur(18px) saturate(1.4);-webkit-backdrop-filter:blur(18px) saturate(1.4);border-bottom:1px solid color-mix(in oklch, var(--ntb-line) 60%, transparent)">
    <a href="{{ route('marketing.home') }}" style="display:flex;align-items:center;gap:10px;font-family:var(--font-serif);font-size:22px;letter-spacing:.06em;color:var(--ntb-text)">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--ntb-accent)"></span>NOCTIS
    </a>
    <nav style="display:flex;align-items:center;gap:30px;position:relative;flex-shrink:0" aria-label="Primary">
        @php($lnk = 'font-size:13.5px;font-weight:500;text-decoration:none;letter-spacing:.01em;')
        <a class="nav-link" style="{{ $lnk }}color:{{ request()->routeIs('marketing.home') ? 'var(--ntb-text)' : 'var(--ntb-muted)' }}" href="{{ route('marketing.home') }}">Home</a>

        <span class="mk-drop" style="position:relative">
            <a class="nav-link" style="{{ $lnk }}display:inline-flex;align-items:center;gap:4px;color:{{ $isSvc ? 'var(--ntb-text)' : 'var(--ntb-muted)' }}"
               href="{{ route('marketing.service', ['slug' => array_key_first($navServices)]) }}">Services <span style="font-size:15px;line-height:1;transform:translateY(2px)">▾</span></a>
            <span class="mk-drop-menu" style="position:absolute;top:100%;left:50%;transform:translateX(-50%);margin-top:14px;background:var(--ntb-surf);border:1px solid var(--ntb-line);border-radius:14px;box-shadow:0 24px 60px -24px rgba(20,30,50,.28);padding:8px;min-width:236px;flex-direction:column">
                @foreach ($navServices as $slug => $svc)
                    <a href="{{ route('marketing.service', ['slug' => $slug]) }}" style="padding:10px 14px;font-size:13.5px;color:var(--ntb-text);border-radius:9px">{{ $svc['nav'] }}</a>
                @endforeach
            </span>
        </span>

        <a class="nav-link" style="{{ $lnk }}color:{{ request()->routeIs('marketing.fleet') ? 'var(--ntb-text)' : 'var(--ntb-muted)' }}" href="{{ route('marketing.fleet') }}">Fleet</a>
        <a class="nav-link" style="{{ $lnk }}color:{{ request()->routeIs('marketing.about') ? 'var(--ntb-text)' : 'var(--ntb-muted)' }}" href="{{ route('marketing.about') }}">About</a>
        <a class="nav-link" style="{{ $lnk }}color:{{ request()->routeIs('marketing.contact') ? 'var(--ntb-text)' : 'var(--ntb-muted)' }}" href="{{ route('marketing.contact') }}">Contact</a>

        <a href="{{ route('account') }}" title="My account" aria-label="My account" style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--ntb-muted)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        </a>
        <a class="cta" href="{{ route('booking.form', ['new' => 1]) }}" style="background:var(--ntb-accent);color:#fff;font-weight:600;font-size:13.5px;padding:9px 20px;border-radius:999px;white-space:nowrap;flex:0 0 auto">Get a Quote</a>
    </nav>
</header>
