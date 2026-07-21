@extends('layouts.marketing')

@section('title', 'About Us — Noctis')

@section('content')
    <section class="ntb-mk-section">
        <div class="ntb-mk-wrap ntb-mk-wrap--narrow">
            <h1 class="ntb-mk-h1" style="font-size:40px;margin:0 0 20px">About Us</h1>
            <p class="ntb-mk-lead" style="margin:0 0 14px">Based in Paris, we provide premium airport transfers and long-distance mobility solutions locally and across Europe.</p>
            <p class="ntb-mk-lead" style="margin:0">Our services are designed for corporate clients, VIP travelers and international passengers seeking structured, private and reliable ground transportation. Our mission is to deliver refined mobility solutions defined by discretion, punctuality and operational precision.</p>
        </div>
    </section>

    <section class="ntb-mk-section ntb-mk-section--alt ntb-mk-section--tight">
        <div class="ntb-mk-wrap ntb-mk-wrap--narrow">
            <div class="ntb-mk-grid ntb-mk-grid--2" style="gap:32px">
                @foreach ($competences as [$title, $body])
                    <div class="ntb-mk-card">
                        <div class="ntb-mk-card-eyebrow">{{ $title }}</div>
                        <p class="ntb-mk-body">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ntb-mk-section">
        <div class="ntb-mk-wrap ntb-mk-wrap--narrow">
            <h2 class="ntb-mk-h2" style="margin:0 0 20px">From airport arrivals to cross-border European journeys, every transfer is coordinated to ensure comfort, control and a consistently elevated standard of service.</h2>
            <p class="ntb-mk-lead" style="font-size:15px;margin:0 0 24px">Through a structured network of trusted transportation partners, we ensure consistent service quality for corporate travelers, private clients and international passengers requiring cross-border mobility.</p>
            <ul class="ntb-mk-dashlist" style="margin:0 0 32px">
                @foreach ($whyChoose as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            <div class="ntb-mk-photo" style="min-height:320px"><span>Photo — cross-border journeys</span></div>
        </div>
    </section>
@endsection
