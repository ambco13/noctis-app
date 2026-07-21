@extends('layouts.marketing')

@section('title', 'Our Fleet — Noctis')

@section('content')
    <section class="ntb-mk-section" style="min-height:70vh">
        <div class="ntb-mk-wrap">
            <h1 class="ntb-mk-h1" style="font-size:36px;margin:0 0 28px">Our Fleet</h1>

            {{-- Filtres décoratifs (parité design) : la sélection réelle du
                 véhicule se fait dans le tunnel de réservation selon le trajet. --}}
            <div class="ntb-mk-filters">
                <select class="ntb-mk-select" aria-label="Vehicle type">
                    <option>Any Type</option><option>Sedan</option><option>Berline</option><option>Van</option>
                </select>
                <select class="ntb-mk-select" aria-label="Make">
                    <option>Any Make</option><option>Mercedes-Benz</option><option>BMW</option>
                </select>
            </div>

            <div class="ntb-mk-grid ntb-mk-grid--3" style="gap:24px">
                @foreach ($fleet as $v)
                    <div class="ntb-mk-vehicle">
                        <div class="ntb-mk-photo"><span>{{ $v['name'] }} photo</span></div>
                        <div class="ntb-mk-vehicle-body">
                            <div class="ntb-mk-vehicle-name">{{ $v['name'] }}</div>
                            <div class="ntb-mk-vehicle-sub">{{ $v['sub'] }}</div>
                            <div class="ntb-mk-vehicle-meta">
                                <span>{{ $v['pax'] }} passengers</span>
                                <span>{{ $v['bags'] }} bags</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:40px">
                <a href="{{ route('booking.form', ['new' => 1]) }}" class="ntb-mk-btn">Book Now</a>
            </div>
        </div>
    </section>
@endsection
