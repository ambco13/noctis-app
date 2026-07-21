@extends('layouts.marketing')

@section('title', 'About Noctis')

@php
    $img = config('marketing.images');
    $photo = fn ($url, $pos = 'center') => "background-image:url('{$url}'),linear-gradient(160deg,var(--ntb-surf2),var(--ntb-bg2));background-size:cover;background-position:{$pos}";
    $competences = [
        ['Professionalism', 'Carefully selected chauffeurs, trained to deliver structured and discreet service at every stage of your journey. We operate modern, well-maintained vehicles to ensure safety, comfort and consistent reliability across all transfers.'],
        ['Commitment', 'With 24/7 availability and simplified booking management, we streamline every stage of your transportation planning. Transparency, responsiveness and operational efficiency remain central to our service standards.'],
    ];
    $points = ['Premium Airport Transfers in Paris', 'Structured European Long-Distance Travel', '24/7 Private Chauffeur Availability', 'Fixed & Transparent Pricing', 'Modern High-End Fleet'];
    $stats = [['24/7', 'Availability, every day of the year'], ['Fixed', 'Fares confirmed before departure'], ['Europe', 'Cross-border reach from Paris'], ['VIP', 'Discretion for every passenger']];
@endphp

@section('content')
    {{-- hero --}}
    <section style="padding:96px 44px 80px;background:var(--ntb-bg)">
        <div data-reveal style="max-width:960px;margin:0 auto">
            <p class="eyb" style="margin-bottom:24px">About Noctis</p>
            <h1 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(34px,5.4vw,72px);line-height:1.04;letter-spacing:-.015em;color:var(--ntb-text);margin:0 0 30px">Refined mobility, defined by discretion and precision.</h1>
            <p style="font-size:18px;line-height:1.75;color:var(--ntb-muted);max-width:720px;margin:0">Based in Paris, we provide premium airport transfers and long-distance mobility solutions locally and across Europe — for corporate clients, VIP travelers and international passengers seeking structured, private and reliable ground transportation.</p>
        </div>
    </section>

    {{-- competences --}}
    <section style="padding:100px 44px;background:var(--ntb-bg2)">
        <div data-reveal class="g-2" style="max-width:1180px;margin:0 auto;gap:20px">
            @foreach ($competences as [$h, $p])
                <div style="padding:36px 34px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:18px;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                    <h3 style="font-family:var(--font-serif);font-weight:400;font-size:26px;color:var(--ntb-text);margin:0 0 12px">{{ $h }}</h3>
                    <p style="font-size:15px;color:var(--ntb-muted);line-height:1.7;margin:0">{{ $p }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- statement + points + photo --}}
    <section style="padding:110px 44px;background:var(--ntb-bg)">
        <div data-reveal class="g-2" style="max-width:1180px;margin:0 auto;gap:64px;align-items:center">
            <div>
                <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(26px,3.2vw,42px);line-height:1.12;letter-spacing:-.01em;color:var(--ntb-text);margin:0 0 20px">From airport arrivals to cross-border journeys — every transfer, elevated.</h2>
                <p style="font-size:16px;color:var(--ntb-muted);line-height:1.7;margin:0 0 28px">Through a structured network of trusted transportation partners, we ensure consistent service quality for corporate travelers, private clients and international passengers requiring cross-border mobility.</p>
                <div style="display:flex;flex-direction:column">
                    @foreach ($points as $i => $t)
                        <div style="display:flex;gap:16px;align-items:center;font-size:16px;color:var(--ntb-text);padding:15px 0;border-bottom:{{ $i < count($points) - 1 ? '1px solid var(--ntb-line-soft)' : 'none' }}">
                            <span style="font-family:var(--font-mono);font-size:11px;color:var(--ntb-accent)">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ $t }}
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="height:520px;border-radius:22px;overflow:hidden;box-shadow:0 40px 80px -40px rgba(20,30,50,.3);{{ $photo($img['road'], 'center 45%') }}"></div>
        </div>
    </section>

    {{-- stats --}}
    <section style="padding:96px 44px;background:color-mix(in oklch, var(--ntb-accent) 8%, var(--ntb-bg))">
        <div data-reveal class="g-4" style="max-width:1180px;margin:0 auto;gap:28px">
            @foreach ($stats as [$n, $l])
                <div style="text-align:center">
                    <div class="stat-tick"></div>
                    <div style="font-family:var(--font-serif);font-weight:400;font-size:clamp(38px,5vw,62px);line-height:1;color:var(--ntb-text)">{{ $n }}</div>
                    <div style="font-size:13.5px;color:var(--ntb-muted);margin-top:12px;line-height:1.5;max-width:190px;margin-inline:auto">{{ $l }}</div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
