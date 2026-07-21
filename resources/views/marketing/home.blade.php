@extends('layouts.marketing')

@section('title', 'Noctis — Private Chauffeur · Paris & Europe')

{{-- Le hero réutilise le VRAI formulaire de réservation : on charge donc les
     mêmes styles et le même runtime JS que la page /reservation (pickers,
     leaflet, autocomplétion, soumission du tunnel). --}}
@push('styles')
    @include('partials.booking-styles')
@endpush
@push('scripts')
    @include('partials.booking-runtime')
@endpush

@section('content')
    {{-- Hero plein écran + formulaire de réservation fonctionnel (partagé). --}}
    @include('booking._hero')

    {{-- Trois teasers. --}}
    <section class="ntb-mk-section">
        <div class="ntb-mk-wrap">
            <div class="ntb-mk-grid ntb-mk-grid--3">
                @php
                    $teasers = [
                        ['AIRPORT TRANSFER', 'Book your private airport transfer in Paris with professional chauffeur service.', 'Book Now', route('booking.form', ['new' => 1])],
                        ['PREMIUM VEHICLE FLEET', 'Luxury vehicles selected for comfort, reliability and airport travel efficiency.', 'Explore Fleet', route('marketing.fleet')],
                        ['CONTACT US', 'Get personalized assistance for your private airport transfer requirements.', 'Get a Quote', route('marketing.contact')],
                    ];
                @endphp
                @foreach ($teasers as [$h, $p, $cta, $href])
                    <div class="ntb-mk-card">
                        <div class="ntb-mk-card-title">{{ $h }}</div>
                        <p class="ntb-mk-body" style="margin:0 0 16px">{{ $p }}</p>
                        <a href="{{ $href }}" class="ntb-mk-link">{{ $cta }} →</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Bandeau services. --}}
    <section class="ntb-mk-section ntb-mk-section--alt ntb-mk-section--tight">
        <div class="ntb-mk-wrap">
            <div class="ntb-mk-grid ntb-mk-grid--6">
                @foreach ($services as $slug => $svc)
                    <a href="{{ route('marketing.service', ['slug' => $slug]) }}" class="ntb-mk-card ntb-mk-chip">
                        <div class="ntb-mk-chip-label">{{ $svc['nav'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Competences / Why choose us. --}}
    <section class="ntb-mk-section">
        <div class="ntb-mk-wrap">
            <div class="ntb-mk-grid ntb-mk-grid--2" style="gap:40px">
                <div>
                    <h2 class="ntb-mk-h2" style="margin:0 0 18px">Our Competences</h2>
                    <div class="ntb-mk-stack">
                        @foreach ($competences as [$title, $body])
                            <div>
                                <div class="ntb-mk-card-eyebrow" style="margin-bottom:6px">{{ $title }}</div>
                                <p class="ntb-mk-body">{{ $body }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h2 class="ntb-mk-h2" style="margin:0 0 18px">Why Choose Us</h2>
                    <ul class="ntb-mk-dashlist">
                        @foreach ($whyChoose as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
