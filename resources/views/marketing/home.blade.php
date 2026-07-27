@extends('layouts.marketing')

@section('title', 'Noctis — Private Chauffeur · Paris & Europe')

@php
    $img = config('marketing.images');
    $book = route('booking.form', ['new' => 1]);
    $svc = config('marketing.services');
    $svcKeys = array_keys($svc);
    $firstSlug = $svcKeys[0];
    $first = $svc[$firstSlug];
    $restSlugs = array_slice($svcKeys, 1);
    // Génère le style background façon photoBg() du design (image + dégradé de repli).
    $photo = fn ($url, $pos = 'center') => "background-image:url('{$url}'),linear-gradient(160deg,var(--ntb-surf2),var(--ntb-bg2));background-size:cover;background-position:{$pos}";
@endphp

@push('styles')
    {{-- Assets du tunnel : le hero de la home EST le vrai formulaire de réservation. --}}
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-public.css') }}?v={{ filemtime(public_path('assets/css/ntb-public.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-pickers.css') }}?v={{ filemtime(public_path('assets/css/ntb-pickers.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ntb-booking-hero.css') }}?v={{ filemtime(public_path('assets/css/ntb-booking-hero.css')) }}">
    {{-- Personnalisation admin (accent, styles) — le hero EST le tunnel. --}}
    {!! \App\Support\Design::styleBlock() !!}
@endpush

@push('scripts')
    @include('partials.booking-runtime')
@endpush

@section('content')
    {{-- ── HERO = FORMULAIRE DE RÉSERVATION (étape 1) ── --}}
    @include('partials.booking-hero-form')

    {{-- ── DESTINATIONS MARQUEE ── --}}
    <section style="background:var(--ntb-bg);padding:28px 0;overflow:hidden">
        <div class="marquee-track" style="display:flex;width:max-content;gap:0">
            @for ($dup = 0; $dup < 2; $dup++)
                <div style="display:flex;align-items:center" aria-hidden="true">
                    @foreach (config('marketing.destinations') as $d)
                        <span style="display:flex;align-items:center;gap:34px;padding:0 34px">
                            <span style="font-family:var(--font-serif);font-size:26px;color:var(--ntb-faint);white-space:nowrap">{{ $d }}</span>
                            <span style="width:5px;height:5px;border-radius:50%;background:var(--ntb-accent);flex:0 0 auto"></span>
                        </span>
                    @endforeach
                </div>
            @endfor
        </div>
    </section>

    {{-- ── STATEMENT ── --}}
    <section style="position:relative;padding:150px 44px;background:var(--ntb-bg);text-align:center;overflow:hidden">
        <div style="position:absolute;top:-10%;left:50%;transform:translateX(-50%);width:720px;height:720px;background:radial-gradient(circle, var(--ntb-accent-soft) 0%, transparent 65%);pointer-events:none"></div>
        <div data-reveal style="position:relative;max-width:1000px;margin:0 auto">
            <p class="eyb" style="margin-bottom:30px">The Noctis promise</p>
            <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(34px,5.6vw,74px);line-height:1.06;letter-spacing:-.02em;color:var(--ntb-text);margin:0">Effortless travel, <span style="color:var(--ntb-accent)">every time you ride.</span></h2>
            <p style="font-size:19px;line-height:1.7;color:var(--ntb-muted);max-width:600px;margin:32px auto 0">Every journey is managed with discretion, punctuality and seamless coordination — from arrival to final destination.</p>
        </div>
    </section>

    {{-- ── CINEMATIC FEATURE: AIRPORT ── --}}
    <section style="position:relative;min-height:88vh;display:flex;align-items:center;overflow:hidden">
        <div style="position:absolute;inset:0;background-attachment:fixed;{{ $photo($img['city'], 'center') }}"></div>
        <div style="position:absolute;inset:0;background:linear-gradient(90deg, rgba(8,12,20,.78) 0%, rgba(8,12,20,.45) 46%, rgba(8,12,20,.06) 100%)"></div>
        <div data-reveal style="position:relative;max-width:1180px;margin:0 auto;padding:0 44px;width:100%;box-sizing:border-box">
            <div class="g-2" style="gap:56px;align-items:start">
                <div style="max-width:460px;color:#fff">
                    <p class="eyb" style="color:#fff;margin-bottom:22px"><x-mk-icon name="wheel" :size="17" color="#fff" /> Private Airport Taxi Transfers</p>
                    <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(38px,6.2vw,80px);line-height:.98;letter-spacing:-.02em;margin:0 0 24px">Your Seamless Paris Airport Transfer.</h2>
                    <a class="alink" style="color:#fff" href="{{ $book }}">Book a transfer <i>→</i></a>
                </div>
                <div class="feat-cols" style="display:grid;grid-template-columns:1fr 1fr;gap:32px;padding-top:14px">
                    <div>
                        <div style="font-family:var(--font-serif);font-size:19px;color:#fff;margin-bottom:12px;line-height:1.2">High-Quality Airport Taxi Service</div>
                        <p style="font-size:15px;line-height:1.65;color:rgba(255,255,255,.72);margin:0">Everything is arranged for you before you arrive. Our airport taxi and private transfer service offers professional drivers, clean vehicles and punctual pickups.</p>
                    </div>
                    <div>
                        <div style="font-family:var(--font-serif);font-size:19px;color:#fff;margin-bottom:12px;line-height:1.2">Travel in Comfort</div>
                        <p style="font-size:15px;line-height:1.65;color:rgba(255,255,255,.72);margin:0">Your seamless Paris airport transfer to CDG, Orly &amp; Beauvais. With our private airport taxi service, your arrival is already taken care of.</p>
                    </div>
                </div>
            </div>
            <blockquote data-reveal style="max-width:640px;margin:70px auto 0;text-align:center">
                <div aria-hidden="true" style="font-family:var(--font-serif);font-size:52px;line-height:.5;color:var(--ntb-accent);margin-bottom:14px">&ldquo;</div>
                <p style="font-size:16px;line-height:1.7;color:rgba(255,255,255,.85);margin:0">Thousands of travelers choose our airport taxi service for punctuality, comfort and peace of mind. Your airport transfer is planned in advance, so you can arrive relaxed.</p>
            </blockquote>
        </div>
    </section>

    {{-- ── EUROPE PANEL ── --}}
    <section style="background:var(--ntb-surf2);color:var(--ntb-text);padding:130px 44px">
        <div data-reveal style="max-width:1180px;margin:0 auto">
            <p class="eyb" style="margin-bottom:22px">Europe-Wide</p>
            <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(34px,6vw,78px);line-height:1.02;letter-spacing:-.02em;margin:0 0 56px;color:var(--ntb-text)">Borders, handled.</h2>
            <div class="routes-grid" style="gap:0;border-top:1px solid var(--ntb-line)">
                @foreach (config('marketing.routes') as $i => [$a, $b])
                    <div class="route" style="padding:30px 20px;padding-left:{{ $i === 0 ? '0' : '20px' }};border-right:{{ $i < 3 ? '1px solid var(--ntb-line)' : 'none' }}">
                        <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:.12em;color:var(--ntb-faint);margin-bottom:14px">ROUTE {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                        <div style="display:flex;align-items:center;gap:12px;font-family:var(--font-serif);font-size:22px;color:var(--ntb-text)">{{ $a }}<span class="route-line" style="flex-grow:1;flex-basis:0;height:1px;background:var(--ntb-accent);min-width:16px"></span>{{ $b }}</div>
                    </div>
                @endforeach
            </div>
            <p style="font-size:16.5px;line-height:1.7;color:var(--ntb-muted);margin:44px 0 28px">Direct long-distance departures with chauffeurs experienced in cross-border routing and logistics — a smooth, flexible alternative to conventional travel.</p>
            <a class="alink" href="{{ route('marketing.service', ['slug' => 'worldwide-transportation']) }}">Explore Europe-wide <i>→</i></a>
        </div>
    </section>

    {{-- ── FLEET TEASER ── --}}
    <section style="padding:120px 44px;background:var(--ntb-bg)">
        <div style="max-width:1180px;margin:0 auto">
            <div data-reveal style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:48px;flex-wrap:wrap;gap:16px">
                <div>
                    <p class="eyb" style="margin-bottom:16px">The Fleet</p>
                    <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(30px,4vw,54px);letter-spacing:-.01em;color:var(--ntb-text);margin:0;line-height:1.02">Chosen for comfort.</h2>
                </div>
                <a class="alink" href="{{ route('marketing.fleet') }}">See the full fleet <i>→</i></a>
            </div>
            <div class="g-3" style="gap:20px">
                @foreach ([['Sedan', 'CHR or similar', 4, 3], ['Berline', 'Classe E — Mercedes-Benz', 4, 4], ['Van', 'Classe V or similar', 6, 6]] as $i => [$n, $s, $p, $b])
                    <div data-reveal class="lift" style="transition-delay:{{ $i * 0.08 }}s;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:18px;overflow:hidden;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                        <div style="height:160px;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,var(--ntb-surf2),var(--ntb-bg2));border-bottom:1px solid var(--ntb-line-soft)"><x-mk-icon name="car" :size="54" :stroke="1.1" color="var(--ntb-accent)" style="opacity:.42" /></div>
                        <div style="padding:20px 22px 22px">
                            <div style="font-family:var(--font-serif);font-size:22px;color:var(--ntb-text)">{{ $n }}</div>
                            <div style="font-size:13px;color:var(--ntb-muted);margin-bottom:14px">{{ $s }}</div>
                            <div style="display:flex;gap:20px;font-size:13px;color:var(--ntb-muted);border-top:1px solid var(--ntb-line-soft);padding-top:14px">
                                <span style="display:inline-flex;align-items:center;gap:7px"><x-mk-icon name="users" :size="16" color="var(--ntb-accent)" />{{ $p }} passengers</span><span style="display:inline-flex;align-items:center;gap:7px"><x-mk-icon name="bag" :size="16" color="var(--ntb-accent)" />{{ $b }} bags</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── STATS BAND ── --}}
    <section style="padding:100px 44px;background:linear-gradient(to bottom, var(--ntb-bg) 0%, color-mix(in oklch, var(--ntb-accent) 8%, var(--ntb-bg)) 12%, color-mix(in oklch, var(--ntb-accent) 8%, var(--ntb-bg)) 88%, var(--ntb-bg) 100%)">
        <div data-reveal class="g-4" style="max-width:1180px;margin:0 auto;gap:28px">
            @foreach (config('marketing.stats') as [$n, $l, $ic])
                <div style="text-align:center">
                    <div style="display:flex;justify-content:center;margin-bottom:16px;color:var(--ntb-accent)"><x-mk-icon :name="$ic" :size="30" :stroke="1.6" color="var(--ntb-accent)" /></div>
                    <div style="font-family:var(--font-serif);font-weight:400;font-size:clamp(38px,5vw,64px);line-height:1;color:var(--ntb-text)">{{ $n }}</div>
                    <div style="font-size:13.5px;color:var(--ntb-muted);margin-top:12px;line-height:1.5;max-width:190px;margin-inline:auto">{{ $l }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── SERVICES BENTO ── --}}
    <section style="padding:120px 44px;background:var(--ntb-bg)">
        <div style="max-width:1180px;margin:0 auto">
            <div data-reveal style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:48px;flex-wrap:wrap;gap:16px">
                <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(30px,4vw,54px);letter-spacing:-.01em;color:var(--ntb-text);margin:0;max-width:560px;line-height:1.02">What we can do for you.</h2>
                <a class="alink" style="font-size:14px" href="{{ route('marketing.contact') }}">Talk to our team <i>→</i></a>
            </div>
            <div class="svc-bento">
                <a data-reveal class="svc lift zoomwrap" href="{{ route('marketing.service', ['slug' => $firstSlug]) }}" style="position:relative;overflow:hidden;border-radius:20px;min-height:420px;display:flex;flex-direction:column;justify-content:flex-end;box-shadow:0 30px 70px -40px rgba(20,30,50,.35)">
                    <div class="zoom" style="position:absolute;inset:0;{{ $photo($img['airport'], 'center 55%') }}"></div>
                    <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(8,12,20,.9) 0%, rgba(8,12,20,.5) 42%, rgba(8,12,20,.12) 100%)"></div>
                    <div style="position:relative;padding:40px 38px;color:#fff">
                        <span style="font-family:var(--font-mono);font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.72)">Most booked</span>
                        <div style="font-family:var(--font-serif);font-size:clamp(30px,3.4vw,42px);margin:12px 0 12px;line-height:1.04">{{ $first['nav'] }}</div>
                        <p style="font-size:15.5px;color:rgba(255,255,255,.86);line-height:1.6;margin:0 0 22px;max-width:440px">{{ $first['sub'] }}</p>
                        <span class="alink" style="color:#fff">Discover the service <i>→</i></span>
                    </div>
                </a>
                <div class="svc-grid">
                    @foreach ($restSlugs as $i => $slug)
                        <a data-reveal class="svc lift" href="{{ route('marketing.service', ['slug' => $slug]) }}" style="transition-delay:{{ $i * 0.06 }}s;position:relative;overflow:hidden;padding:24px 24px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:16px;min-height:190px;display:flex;flex-direction:column;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                            <span class="num" style="font-family:var(--font-mono);font-size:12px;color:var(--ntb-accent)">{{ str_pad($i + 2, 2, '0', STR_PAD_LEFT) }}</span>
                            <div style="font-family:var(--font-serif);font-size:21px;color:var(--ntb-text);margin:auto 0 8px;line-height:1.12">{{ $svc[$slug]['nav'] }}</div>
                            <p style="font-size:13px;color:var(--ntb-muted);line-height:1.5;margin:0;padding-right:26px">{{ $svc[$slug]['sub'] }}</p>
                            <span class="arw" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ── WHY CHOOSE US ── --}}
    <section style="padding:120px 44px;background:var(--ntb-bg2)">
        <div style="max-width:1180px;margin:0 auto">
            <div data-reveal style="margin-bottom:52px">
                <p class="eyb" style="margin-bottom:20px">Why choose us</p>
                <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(30px,4vw,54px);letter-spacing:-.01em;color:var(--ntb-text);margin:0;line-height:1.03">Our Paris airport transfer service.</h2>
            </div>
            <div data-reveal class="g-4" style="gap:18px">
                @foreach (config('marketing.why') as [$h, $p, $ic])
                    <div class="lift" style="position:relative;padding:40px 26px 28px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:16px;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                        <div style="position:absolute;top:0;left:50%;transform:translate(-50%,-50%);width:48px;height:48px;border-radius:12px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);color:var(--ntb-accent);display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px -8px rgba(20,30,50,.15)"><x-mk-icon :name="$ic" :size="24" color="var(--ntb-accent)" /></div>
                        <div style="font-family:var(--font-serif);font-size:20px;color:var(--ntb-text);margin:0 0 10px;line-height:1.15;text-align:center">{{ $h }}</div>
                        <p style="font-size:13.5px;color:var(--ntb-muted);line-height:1.6;margin:0;text-align:center">{{ $p }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── TESTIMONIALS ── --}}
    <section style="padding:120px 44px;background:var(--ntb-bg)">
        <div style="max-width:1180px;margin:0 auto">
            <div data-reveal style="margin-bottom:52px">
                <p class="eyb" style="margin-bottom:20px">What travelers say</p>
                <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(30px,4vw,54px);letter-spacing:-.01em;color:var(--ntb-text);margin:0;line-height:1.03">Trusted by travelers worldwide.</h2>
            </div>
            <div data-reveal class="g-3" style="gap:18px">
                @foreach (config('marketing.testimonials') as [$q, $name, $country])
                    <div style="padding:32px 30px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:18px;display:flex;flex-direction:column;gap:20px;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                        <div aria-hidden="true" style="font-family:var(--font-serif);font-size:52px;line-height:.5;color:var(--ntb-accent);height:22px">&ldquo;</div>
                        <p style="font-size:16px;line-height:1.6;color:var(--ntb-text);margin:0;flex:1">{{ $q }}</p>
                        <div style="display:flex;align-items:center;gap:12px;border-top:1px solid var(--ntb-line-soft);padding-top:18px">
                            <span style="width:38px;height:38px;border-radius:50%;background:var(--ntb-accent-soft);color:var(--ntb-accent);display:flex;align-items:center;justify-content:center;font-family:var(--font-serif);font-size:17px;flex:0 0 auto">{{ mb_substr($name, 0, 1) }}</span>
                            <div><div style="font-size:14px;font-weight:650;color:var(--ntb-text)">{{ $name }}</div><div style="font-size:12px;color:var(--ntb-faint)">{{ $country }}</div></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── CLOSING CTA ── --}}
    <section style="padding:90px 24px;text-align:center;background:oklch(0.96 0.025 236)">
        <div data-reveal>
            <p style="font-family:var(--font-mono);font-size:12px;text-transform:uppercase;letter-spacing:.24em;color:oklch(0.5 0.115 236);margin-bottom:24px">Paris &amp; Europe · 24/7</p>
            <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(36px,6vw,84px);line-height:1;color:oklch(0.22 0.02 245);margin:0 0 34px;letter-spacing:-.02em">Ready when you are.</h2>
            <a class="cta" href="{{ $book }}" style="display:inline-block;background:var(--ntb-accent);color:#fff;font-weight:700;font-size:16px;padding:18px 46px;border-radius:999px;box-shadow:0 20px 40px -20px rgba(8,14,28,.25)">Estimate my ride</a>
        </div>
    </section>
@endsection
