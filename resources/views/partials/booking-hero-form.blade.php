{{-- Hero + formulaire fonctionnel de réservation (étape 1).
     Partagé entre la home (/) et le layout booking-hero. Attend $prefill.
     Le markup et les data-* sont ceux attendus par ntb-public.js — ne pas
     renommer sans adapter le JS du tunnel. --}}
<div class="ntb-scope ntb-home ntb-hero-v2">
  <div class="ntb-hero-inner">
    <div class="ntb-hero-text">
        <p class="ntb-hero-eyebrow">{{ __('Paris & Europe') }}</p>
        {{-- Deux <br> exclusifs (CSS) : coupure après « chauffeur » sur grand
             écran, avant sur téléphone (≤640px), où la 1re ligne ne tient pas.
             Les espaces autour des <br> assurent le mot joint quand l'un des
             deux est masqué (l'espace en début de ligne est ignoré). --}}
        <h1 class="ntb-hero-title">{!! __('Your chauffeur<br class="ntb-br-m"> is <br class="ntb-br-d">waiting.') !!}</h1>
        <p class="ntb-hero-tagline">{!! __('Fixed price known in advance.<br>Book in under a minute, secure payment.') !!}</p>
    </div>
    <form class="ntb-home-form" method="post" action="{{ route('booking.step1') }}" data-ntb-step1>
        @csrf
        <input type="hidden" name="NTB2_step1_submit" value="1" />
        <input type="hidden" name="NTB2_pickup_place_id" id="ntb-pickup-place-id" value="{{ $prefill['pickup_place_id'] }}" />
        <input type="hidden" name="NTB2_dropoff_place_id" id="ntb-dropoff-place-id" value="{{ $prefill['dropoff_place_id'] }}" />

        <div class="ntb-hf-grid">
            <div class="ntb-field" data-ntb-autocomplete data-place-id-target="ntb-pickup-place-id">
                <label for="ntb-pickup">{{ __('Departure') }}</label>
                <div class="ntb-field-input">
                    <span class="ntb-field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg>
                    </span>
                    <input type="text" id="ntb-pickup" name="NTB2_pickup" autocomplete="off"
                        placeholder="{{ __('Pickup address') }}"
                        value="{{ $prefill['pickup_address'] }}" required />
                </div>
                <div class="ntb-ac-list" hidden></div>
                <div class="ntb-field-err" hidden></div>
            </div>

            <div class="ntb-field" data-ntb-autocomplete data-place-id-target="ntb-dropoff-place-id">
                <label for="ntb-dropoff">{{ __('Arrival') }}</label>
                <div class="ntb-field-input">
                    <span class="ntb-field-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <input type="text" id="ntb-dropoff" name="NTB2_dropoff" autocomplete="off"
                        placeholder="{{ __('Drop-off address') }}"
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
                        placeholder="dd/mm/yyyy" autocomplete="off" />
                    <input type="date" id="ntb-date" name="NTB2_date"
                        value="{{ $prefill['ride_date'] }}"
                        min="{{ now()->format('Y-m-d') }}" required />
                </div>
                <div class="ntb-field-err" hidden></div>
            </div>

            <div class="ntb-field">
                <label for="ntb-time">{{ __('Time') }}</label>
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
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.6-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9L2.1 10.9A3 3 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                {{ __('Estimate my ride') }}
            </div>
        </div>
    </form>
  </div>
</div>
