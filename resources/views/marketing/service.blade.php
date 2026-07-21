@extends('layouts.marketing')

@section('title', $service['title'] . ' — Noctis')

@section('content')
    <section class="ntb-mk-section">
        <div class="ntb-mk-wrap">
            <nav class="ntb-mk-tabs" aria-label="Services">
                @foreach ($services as $s => $svc)
                    <a class="ntb-mk-tab {{ $s === $slug ? 'is-active' : '' }}"
                       href="{{ route('marketing.service', ['slug' => $s]) }}">{{ $svc['nav'] }}</a>
                @endforeach
            </nav>
            <h1 class="ntb-mk-h1" style="max-width:800px;margin:0 0 20px">{{ $service['hero_title'] }}</h1>
            <p class="ntb-mk-lead" style="max-width:760px;margin:0 0 36px">{{ $service['intro'] }}</p>
            <div class="ntb-mk-photo" style="min-height:360px"><span>{{ $service['title'] }} — photo</span></div>
        </div>
    </section>

    <section class="ntb-mk-section ntb-mk-section--alt ntb-mk-section--tight">
        <div class="ntb-mk-wrap">
            <div class="ntb-mk-split">
                <div>
                    <h2 class="ntb-mk-h2" style="margin:0 0 14px">{{ $service['sub'] }}</h2>
                    <p class="ntb-mk-lead" style="font-size:15px;margin:0 0 20px">{{ $service['sub_text'] }}</p>
                    <ul class="ntb-mk-bullets">
                        @foreach ($service['bullets'] as $b)
                            <li>{{ $b }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="ntb-mk-photo" style="min-height:300px"><span>Photo</span></div>
            </div>
        </div>
    </section>

    <section class="ntb-mk-section">
        <div class="ntb-mk-wrap">
            <h3 class="ntb-mk-h3" style="margin:0 0 28px">Why Choose Our {{ $service['nav'] }} Service</h3>
            <div class="ntb-mk-grid ntb-mk-grid--3">
                @foreach ($service['why'] as [$h, $p])
                    <div class="ntb-mk-card" style="padding:22px">
                        <div class="ntb-mk-card-title" style="font-weight:650">{{ $h }}</div>
                        <div class="ntb-mk-body" style="line-height:1.55">{{ $p }}</div>
                    </div>
                @endforeach
            </div>
            <div style="margin-top:40px;text-align:center">
                <a href="{{ route('booking.form', ['new' => 1]) }}" class="ntb-mk-btn" style="padding:14px 30px">Book Now</a>
            </div>
        </div>
    </section>
@endsection
