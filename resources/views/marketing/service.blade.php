@extends('layouts.marketing')

@section('title', $service['title'] . ' — Noctis')

@php
    $img = config('marketing.images');
    $book = route('booking.form', ['new' => 1]);
    $services = config('marketing.services');
    $photo = fn ($url, $pos = 'center') => "background-image:url('{$url}'),linear-gradient(160deg,var(--ntb-surf2),var(--ntb-bg2));background-size:cover;background-position:{$pos}";
    $navLower = strtolower($service['nav']);
@endphp

@section('content')
    {{-- tabs --}}
    <section style="padding:40px 44px 0;background:var(--ntb-bg)">
        <div style="max-width:1180px;margin:0 auto;display:flex;gap:10px;flex-wrap:wrap">
            @foreach ($services as $s => $x)
                @php($on = $s === $slug)
                <a href="{{ route('marketing.service', ['slug' => $s]) }}" style="font-size:13px;font-weight:600;padding:9px 18px;border-radius:999px;border:1px solid;white-space:nowrap;border-color:{{ $on ? 'transparent' : 'var(--ntb-line)' }};background:{{ $on ? 'var(--ntb-accent)' : 'transparent' }};color:{{ $on ? '#fff' : 'var(--ntb-muted)' }}">{{ $x['nav'] }}</a>
            @endforeach
        </div>
    </section>

    {{-- hero --}}
    <section style="padding:56px 44px 90px;background:var(--ntb-bg)">
        <div data-reveal style="max-width:1180px;margin:0 auto">
            <p class="eyb" style="margin-bottom:20px">{{ $service['nav'] }}</p>
            <h1 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(34px,5.2vw,68px);line-height:1.02;letter-spacing:-.015em;color:var(--ntb-text);margin:0 0 26px">{{ $service['hero_title'] }}</h1>
            <p style="font-size:18px;line-height:1.7;color:var(--ntb-muted);margin:0 0 46px">{{ $service['intro'] }}</p>
            <div style="height:clamp(280px,42vw,500px);border-radius:24px;overflow:hidden;box-shadow:0 50px 100px -50px rgba(20,30,50,.4);{{ $photo($img['city'], 'center 55%') }}"></div>
        </div>
    </section>

    {{-- sub + bullets --}}
    <section style="padding:100px 44px;background:var(--ntb-bg2)">
        <div data-reveal class="g-2" style="max-width:1180px;margin:0 auto;gap:64px;align-items:center">
            <div>
                <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(26px,3vw,40px);line-height:1.1;letter-spacing:-.01em;color:var(--ntb-text);margin:0 0 18px">{{ $service['sub'] }}</h2>
                <p style="font-size:16px;line-height:1.7;color:var(--ntb-muted);margin:0 0 26px">{{ $service['sub_text'] }}</p>
                <div style="display:flex;flex-direction:column">
                    @php($n = count($service['bullets']))
                    @foreach ($service['bullets'] as $i => $b)
                        <div style="display:flex;gap:16px;align-items:center;font-size:15.5px;color:var(--ntb-text);padding:14px 0;border-bottom:{{ $i < $n - 1 ? '1px solid var(--ntb-line-soft)' : 'none' }}">
                            <span style="font-family:var(--font-mono);font-size:11px;color:var(--ntb-accent)">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ $b }}
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="height:460px;border-radius:22px;overflow:hidden;box-shadow:0 40px 80px -40px rgba(20,30,50,.3);{{ $photo($img['interior'], 'center 40%') }}"></div>
        </div>
    </section>

    {{-- why --}}
    <section style="padding:110px 44px;background:var(--ntb-bg)">
        <div style="max-width:1180px;margin:0 auto">
            <h2 data-reveal style="font-family:var(--font-serif);font-weight:400;font-size:clamp(28px,3.6vw,48px);letter-spacing:-.01em;color:var(--ntb-text);margin:0 0 46px;line-height:1.05;max-width:640px">Why choose our {{ $navLower }} service.</h2>
            <div data-reveal class="g-3" style="gap:18px">
                @foreach ($service['why'] as $i => [$h, $p])
                    <div class="lift" style="padding:28px 26px;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:16px;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                        <div style="font-family:var(--font-mono);font-size:12px;color:var(--ntb-accent);margin-bottom:14px">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                        <div style="font-family:var(--font-serif);font-size:20px;color:var(--ntb-text);margin:0 0 8px;line-height:1.15">{{ $h }}</div>
                        <p style="font-size:14px;color:var(--ntb-muted);line-height:1.6;margin:0">{{ $p }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section style="padding:120px 24px;text-align:center;background:oklch(0.96 0.025 236)">
        <div data-reveal>
            <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(30px,5vw,62px);color:oklch(0.22 0.02 245);margin:0 0 30px;line-height:1.04;letter-spacing:-.01em">Book your {{ $navLower }}.</h2>
            <a class="cta" href="{{ $book }}" style="display:inline-block;background:var(--ntb-accent);color:#fff;font-weight:700;font-size:16px;padding:17px 44px;border-radius:999px;box-shadow:0 20px 40px -20px rgba(8,14,28,.25)">Estimate my ride</a>
        </div>
    </section>
@endsection
