@extends('layouts.app')

@section('title', __('Réservation'))

@section('content')
<div class="ntb-scope ntb-home">
  <div class="ntb-hero-inner">
    <div class="ntb-hero-text">
        <p class="ntb-hero-eyebrow">{{ __('Paris & régions') }}</p>
        {{-- Deux <br> exclusifs (CSS) : coupure après « vous » sur grand écran,
             avant « vous » sur téléphone (≤640px), où la 1re ligne longue ne
             tient pas. Les espaces autour des <br> assurent le mot joint quand
             l'un des deux est masqué (l'espace en début de ligne est ignoré). --}}
        <h1 class="ntb-hero-title">{!! __('Votre chauffeur<br class="ntb-br-m"> vous <br class="ntb-br-d">attend.') !!}</h1>
        <p class="ntb-hero-tagline">{!! __("Prix tout compris connu d'avance.<br>Réservation en moins d'une minute, paiement sécurisé.") !!}</p>
    </div>
    <form class="ntb-home-form" method="post" action="{{ route('booking.step1') }}" data-ntb-step1>
        @csrf
        <input type="hidden" name="NTB2_step1_submit" value="1" />
        <input type="hidden" name="NTB2_pickup_place_id" id="ntb-pickup-place-id" value="{{ $prefill['pickup_place_id'] }}" />
        <input type="hidden" name="NTB2_dropoff_place_id" id="ntb-dropoff-place-id" value="{{ $prefill['dropoff_place_id'] }}" />

        <div class="ntb-hf-grid">
            <div class="ntb-field" data-ntb-autocomplete data-place-id-target="ntb-pickup-place-id">
                <label for="ntb-pickup">{{ __('Départ') }}</label>
                <div class="ntb-field-input">
                    <span class="ntb-field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                    </span>
                    <input type="text" id="ntb-pickup" name="NTB2_pickup" autocomplete="off"
                        placeholder="{{ __('Adresse de départ') }}"
                        value="{{ $prefill['pickup_address'] }}" required />
                </div>
                <div class="ntb-ac-list" hidden></div>
                <div class="ntb-field-err" hidden></div>
            </div>

            <div class="ntb-field" data-ntb-autocomplete data-place-id-target="ntb-dropoff-place-id">
                <label for="ntb-dropoff">{{ __('Arrivée') }}</label>
                <div class="ntb-field-input">
                    <span class="ntb-field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <input type="text" id="ntb-dropoff" name="NTB2_dropoff" autocomplete="off"
                        placeholder="{{ __("Adresse d'arrivée") }}"
                        value="{{ $prefill['dropoff_address'] }}" required />
                </div>
                <div class="ntb-ac-list" hidden></div>
                <div class="ntb-field-err" hidden></div>
            </div>

            @php
                $dateDisplay = '';
                if (! empty($prefill['ride_date'])) {
                    $d = \DateTime::createFromFormat('Y-m-d', $prefill['ride_date']);
                    $dateDisplay = $d ? $d->format('d/m/Y') : '';
                }
            @endphp
            <div class="ntb-field">
                <label for="ntb-date">{{ __('Date') }}</label>
                <div class="ntb-field-input">
                    <span class="ntb-field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <input type="text" class="ntb-date-pre"
                        value="{{ $dateDisplay }}"
                        placeholder="jj/mm/aaaa" autocomplete="off" />
                    <input type="date" id="ntb-date" name="NTB2_date"
                        value="{{ $prefill['ride_date'] }}"
                        min="{{ now()->format('Y-m-d') }}" required />
                </div>
                <div class="ntb-field-err" hidden></div>
            </div>

            <div class="ntb-field">
                <label for="ntb-time">{{ __('Heure') }}</label>
                <div class="ntb-field-input">
                    <span class="ntb-field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                    </span>
                    <input type="text" class="nc-time-display"
                        value="{{ $prefill['ride_time'] }}"
                        placeholder="--:--" maxlength="5" autocomplete="off" />
                    <input type="time" id="ntb-time" name="NTB2_time"
                        value="{{ $prefill['ride_time'] }}" required />
                </div>
                <div class="ntb-field-err" hidden></div>
            </div>

            <span class="ntb-hf-sep" aria-hidden="true"></span>

            <div role="button" tabindex="0" class="ntb-btn ntb-btn-primary ntb-hf-go" data-ntb-step1-submit>
                {{ __('Estimer ma course') }}
            </div>
        </div>
    </form>
  </div>
</div>
@endsection