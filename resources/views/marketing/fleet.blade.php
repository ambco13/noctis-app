@extends('layouts.marketing')

@section('title', 'Our Fleet — Noctis')

@php
    $img = config('marketing.images');
    $book = route('booking.form', ['new' => 1]);
    $photo = fn ($url, $pos = 'center') => "background-image:url('{$url}'),linear-gradient(160deg,var(--ntb-surf2),var(--ntb-bg2));background-size:cover;background-position:{$pos}";
    $fleet = config('marketing.fleet');
    // Options de filtre générées depuis les vraies données (pas de valeur fantôme).
    $types = collect($fleet)->pluck('type')->unique()->values();
    $makes = collect($fleet)->pluck('make')->unique()->values();
@endphp

@section('content')
    <div style="background:var(--ntb-bg)">
        <section style="padding:72px 44px 40px">
            <div data-reveal style="max-width:1180px;margin:0 auto">
                <p class="eyb" style="margin-bottom:20px">The Fleet</p>
                <h1 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(38px,6vw,84px);line-height:1;letter-spacing:-.02em;color:var(--ntb-text);margin:0 0 22px">Chosen for comfort.</h1>
                <p style="font-size:18px;line-height:1.7;color:var(--ntb-muted);margin:0">Modern, well-maintained vehicles selected for comfort, reliability and airport-travel efficiency — each presented to an impeccable standard.</p>
            </div>
        </section>

        <section style="padding:0 44px 110px">
            <div style="max-width:1180px;margin:0 auto">
                <div data-reveal class="mk-filters" style="display:flex;gap:12px;margin-bottom:36px;flex-wrap:wrap">
                    <div class="mk-filter" data-filter="type">
                        <button type="button" class="mk-filter-btn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="mk-filter-val" data-default="Any Type">Any Type</span>
                            <svg class="mk-filter-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="mk-filter-menu" role="listbox">
                            <button type="button" class="mk-filter-opt is-active" data-value="">Any Type</button>
                            @foreach ($types as $t)
                                <button type="button" class="mk-filter-opt" data-value="{{ $t }}">{{ $t }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mk-filter" data-filter="make">
                        <button type="button" class="mk-filter-btn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="mk-filter-val" data-default="Any Make">Any Make</span>
                            <svg class="mk-filter-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="mk-filter-menu" role="listbox">
                            <button type="button" class="mk-filter-opt is-active" data-value="">Any Make</button>
                            @foreach ($makes as $mk)
                                <button type="button" class="mk-filter-opt" data-value="{{ $mk }}">{{ $mk }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div data-fleet-grid class="g-3" style="gap:22px">
                    @foreach ($fleet as $v)
                        <div data-reveal class="lift" data-type="{{ $v['type'] }}" data-make="{{ $v['make'] }}" style="transition-delay:{{ $loop->index * 0.09 }}s;background:var(--ntb-surf);border:1px solid var(--ntb-line-soft);border-radius:20px;overflow:hidden;box-shadow:0 1px 2px rgba(20,30,50,.04)">
                            <div style="height:210px;position:relative;{{ $photo($img[$v['img']], $v['pos']) }}">
                                <span style="position:absolute;top:14px;left:14px;font-family:var(--font-mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;padding:5px 11px;border-radius:999px;background:rgba(255,255,255,.9);color:oklch(0.5 0.115 236)">{{ $v['tier'] }}</span>
                            </div>
                            <div style="padding:22px 24px 24px">
                                <div style="font-family:var(--font-serif);font-size:24px;color:var(--ntb-text);line-height:1.1">{{ $v['name'] }}</div>
                                <div style="font-size:13px;color:var(--ntb-muted);margin-bottom:18px">{{ $v['sub'] }}</div>
                                <div style="display:flex;gap:24px;font-size:13.5px;color:var(--ntb-muted);border-top:1px solid var(--ntb-line-soft);padding-top:16px">
                                    <span style="display:inline-flex;align-items:center;gap:7px"><x-mk-icon name="users" :size="17" color="var(--ntb-accent)" />{{ $v['pax'] }} passengers</span><span style="display:inline-flex;align-items:center;gap:7px"><x-mk-icon name="bag" :size="17" color="var(--ntb-accent)" />{{ $v['bags'] }} bags</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p data-fleet-empty hidden style="color:var(--ntb-muted);font-size:15px;margin:8px 2px 0">No vehicle matches these filters.</p>
            </div>
        </section>

        <section style="padding:120px 24px;text-align:center;background:oklch(0.96 0.025 236)">
            <div data-reveal>
                <h2 style="font-family:var(--font-serif);font-weight:400;font-size:clamp(30px,5vw,62px);color:oklch(0.22 0.02 245);margin:0 0 30px;line-height:1.04;letter-spacing:-.01em">Find your vehicle.</h2>
                <a class="cta" href="{{ $book }}" style="display:inline-block;background:var(--ntb-accent);color:#fff;font-weight:700;font-size:16px;padding:17px 44px;border-radius:999px;box-shadow:0 20px 40px -20px rgba(8,14,28,.25)">Estimate my ride</a>
            </div>
        </section>
    </div>
@endsection
