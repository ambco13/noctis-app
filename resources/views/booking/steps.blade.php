@extends('layouts.app')

@section('title', __('Ma réservation'))

@section('content')
@php
    $hasStep1 = ! empty($step1['pickup_address']) && ! empty($step1['dropoff_address']);
    $hasStripe = \App\Support\Secrets::stripeKey() !== '';
    $hasPaypal = \App\Support\Secrets::paypalClientId() !== '';
@endphp
<div class="ntb-scope"
    data-ntb-steps
    data-booking-url="{{ route('booking.form') }}"
    data-pickup="{{ $step1['pickup_address'] }}"
    data-dropoff="{{ $step1['dropoff_address'] }}"
    data-date="{{ $step1['ride_date'] }}"
    data-time="{{ $step1['ride_time'] }}"
    data-pickup-pid="{{ $step1['pickup_place_id'] }}"
    data-dropoff-pid="{{ $step1['dropoff_place_id'] }}">

    @if (! $hasStep1)
        <div class="ntb-empty-flow">
            <p>{{ __("Aucune course en cours. Veuillez d'abord renseigner votre trajet.") }}</p>
            <a class="ntb-btn ntb-btn-primary" href="{{ route('booking.form') }}">
                {{ __('Démarrer une réservation') }}
            </a>
        </div>
    @else

        <!-- EN-TÊTE ÉTAPE 2 : titre + progression (dupliquée dans la carte, visible seulement quand le titre sort de l'écran) -->
        <div class="ntb-step2-header" data-ntb-s2-header>
            <div class="ntb-step3-left">
                <div role="button" tabindex="0" class="ntb-back-btn" data-ntb-back hidden aria-label="{{ __('Retour') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" style="display:block;min-width:18px;width:18px;height:18px;fill:none;stroke:oklch(0.965 0.004 240);stroke-width:2.5px;"><path d="M15 18l-6-6 6-6" style="fill:none;stroke:oklch(0.965 0.004 240);stroke-width:2.5px;"/></svg>
                </div>
                <h2 class="ntb-step-title" style="margin: 0;" data-ntb-step-title
                    data-title-2="{{ __('Choisissez votre expérience') }}"
                    data-title-3="{{ __('Vos coordonnées et le paiement') }}"
                    data-title-4="{{ __('Votre réservation est confirmée') }}">{{ __('Choisissez votre expérience') }}</h2>
            </div>
            <div class="ntb-prog" aria-hidden="true">
                <div class="ntb-prog-node active" data-step="2">
                    <span class="ntb-prog-bub">2</span>
                    <span class="ntb-prog-lab">{{ __('Véhicule') }}</span>
                </div>
                <span class="ntb-prog-line"></span>
                <div class="ntb-prog-node" data-step="3">
                    <span class="ntb-prog-bub">3</span>
                    <span class="ntb-prog-lab">{{ __('Paiement') }}</span>
                </div>
                <span class="ntb-prog-line"></span>
                <div class="ntb-prog-node" data-step="4">
                    <span class="ntb-prog-bub">4</span>
                    <span class="ntb-prog-lab">{{ __('Confirmation') }}</span>
                </div>
            </div>
        </div>

        <!-- CORPS : deux colonnes flex (contenu | carte + récap) -->
        <div class="ntb-step2-body">

        <!-- COLONNE GAUCHE 65% — toutes les étapes -->
        <div class="ntb-step2-layout">

            <!-- ÉTAPE 2 : véhicules -->
            <div class="ntb-step" data-ntb-screen="2">
                <div class="ntb-step2-main">
                    <div class="ntb-loading" data-ntb-loading>
                        <span class="ntb-spinner" aria-hidden="true"></span>
                        {{ __('Calcul de votre trajet…') }}
                    </div>
                    <div class="ntb-veh-carousel">
                        <button class="ntb-veh-arrow ntb-veh-prev" type="button" aria-label="{{ __('Précédent') }}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <div class="ntb-veh-wrap" data-ntb-vehicles></div>
                        <button class="ntb-veh-arrow ntb-veh-next" type="button" aria-label="{{ __('Suivant') }}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                    <div class="ntb-flow-error" data-ntb-error hidden></div>

                    <div class="ntb-trust">
                        <div class="ntb-trust-item">
                            <span class="ntb-trust-ico" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
                            </span>
                            <div>
                                <p class="ntb-trust-title">{{ __('Chauffeurs vérifiés') }}</p>
                                <p class="ntb-trust-text">{{ __('Identité et permis contrôlés avant chaque mise en service.') }}</p>
                            </div>
                        </div>
                        <div class="ntb-trust-item">
                            <span class="ntb-trust-ico" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            </span>
                            <div>
                                <p class="ntb-trust-title">{{ __('Paiement sécurisé') }}</p>
                                <p class="ntb-trust-text">{{ __('Carte, Apple Pay, Google Pay ou PayPal — vos données ne transitent jamais par nos serveurs.') }}</p>
                            </div>
                        </div>
                        <div class="ntb-trust-item">
                            <span class="ntb-trust-ico" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
                            </span>
                            <div>
                                <p class="ntb-trust-title">{{ __('Annulation gratuite') }}</p>
                                <p class="ntb-trust-text">{{ __("Jusqu'à 1 heure avant le départ, sans frais.") }}</p>
                            </div>
                        </div>
                        <div class="ntb-trust-item">
                            <span class="ntb-trust-ico" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
                            </span>
                            <div>
                                <p class="ntb-trust-title">{{ __('Suivi en temps réel') }}</p>
                                <p class="ntb-trust-text">{{ __("Position du chauffeur et heure d'arrivée dans votre espace client.") }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÉTAPE 3 : coordonnées + paiement -->
            <section class="ntb-step" data-ntb-screen="3" hidden>
                <div class="ntb-step3-grid">
                    <div class="ntb-step3-form">
                        <div class="ntb-field">
                            <label for="ntb-name">{{ __('Nom complet') }}</label>
                            <div class="ntb-field-input">
                                <input type="text" id="ntb-name" name="ntb_name" autocomplete="name" placeholder="Nom Prénom" required />
                            </div>
                            <div class="ntb-field-err" hidden></div>
                        </div>
                        <div class="ntb-field">
                            <label for="ntb-phone">{{ __('Téléphone') }}</label>
                            <div class="ntb-field-input">
                                <input type="tel" id="ntb-phone" name="ntb_phone" autocomplete="tel"
                                    placeholder="{{ \App\Support\Settings::get('default_country_code', '+33') }} 6 12 34 56 78"
                                    required />
                            </div>
                            <div class="ntb-field-err" hidden></div>
                        </div>
                        <div class="ntb-field">
                            <label for="ntb-email">{{ __('Email') }}</label>
                            <div class="ntb-field-input">
                                <input type="email" id="ntb-email" name="ntb_email" autocomplete="email" placeholder="exemple@email.com" required />
                            </div>
                            <div class="ntb-field-err" hidden></div>
                        </div>
                        <div class="ntb-field">
                            <label for="ntb-message">{{ __('Message / instructions (facultatif)') }}</label>
                            <div class="ntb-field-input ntb-field-textarea">
                                <textarea id="ntb-message" name="ntb_message" autocomplete="off" rows="3"
                                    placeholder="{{ __('N° de vol, étage, bagages…') }}"></textarea>
                            </div>
                        </div>

                        <!-- Choix du moyen de paiement — onglets uniquement si plusieurs moyens disponibles -->
                        @if ($hasStripe && $hasPaypal)
                            <div class="ntb-pay-choice" role="tablist">
                                <button type="button" class="ntb-pay-tab active" data-pay="stripe">
                                    {{ __('Carte / Apple Pay / Google Pay') }}
                                </button>
                                <button type="button" class="ntb-pay-tab" data-pay="paypal">
                                    PayPal
                                </button>
                            </div>
                        @endif

                        <div class="ntb-pay-panel" data-pay-panel="stripe" hidden>
                            <div id="ntb-stripe-element"></div>
                            <div class="ntb-pay-bar">
                                <div role="button" tabindex="0" class="ntb-btn ntb-pay-back" data-ntb-back aria-label="{{ __('Retour') }}" style="display: none; padding: 0; border-radius: 50%; width: 44px; height: 44px; justify-content: center; align-items: center; border: 1.5px solid var(--ntb-line); background: var(--ntb-surf); transition: all 200ms ease;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                </div>
                                <div role="button" tabindex="0" class="ntb-btn ntb-btn-primary ntb-pay-btn" data-ntb-pay-stripe>
                                    {{ __('Payer maintenant') }}
                                </div>
                            </div>
                        </div>

                        <div class="ntb-pay-panel" data-pay-panel="paypal" hidden>
                            <div id="ntb-paypal-buttons"></div>
                        </div>

                        <div class="ntb-flow-error" data-ntb-pay-error hidden></div>
                    </div>

                    <!-- Récapitulatif -->
                    <aside class="ntb-recap" data-ntb-recap></aside>
                </div>
            </section>

            <!-- ÉTAPE 4 : confirmation -->
            <section class="ntb-step ntb-confirm" data-ntb-screen="4" hidden>
                <div class="ntb-confirm-inner" data-ntb-confirm></div>
            </section>

        </div><!-- /ntb-step2-layout -->

        <!-- COLONNE DROITE 35% — carte itinéraire + récapitulatif -->
        <div class="ntb-step2-aside">

            <!-- Copie du 2-3-4 : masquée tant que le titre (dans l'en-tête)
                 est visible à l'écran, JS bascule .ntb-show-prog au scroll. -->
            <div class="ntb-prog ntb-prog--aside" aria-hidden="true">
                <div class="ntb-prog-node active" data-step="2">
                    <span class="ntb-prog-bub">2</span>
                    <span class="ntb-prog-lab">{{ __('Véhicule') }}</span>
                </div>
                <span class="ntb-prog-line"></span>
                <div class="ntb-prog-node" data-step="3">
                    <span class="ntb-prog-bub">3</span>
                    <span class="ntb-prog-lab">{{ __('Paiement') }}</span>
                </div>
                <span class="ntb-prog-line"></span>
                <div class="ntb-prog-node" data-step="4">
                    <span class="ntb-prog-bub">4</span>
                    <span class="ntb-prog-lab">{{ __('Confirmation') }}</span>
                </div>
            </div>

            <!-- Carte Leaflet -->
            <div class="ntb-aside-map-wrap">
                <div id="ntb-route-map" class="ntb-route-map" hidden aria-hidden="true"></div>
            </div>

            <!-- Carte récapitulatif (visible après sélection d'un véhicule) -->
            <div class="ntb-sum-card" data-ntb-sel-bar hidden>

                <!-- En-tête : nom + prix -->
                <div class="ntb-sum-header">
                    <div class="ntb-sum-header-left">
                        <h2 class="ntb-sum-title" data-ntb-sel-name></h2>
                        <p class="ntb-sum-subtitle" data-ntb-sel-subtitle></p>
                    </div>
                    <span class="ntb-sum-price" data-ntb-sel-price></span>
                </div>

                <!-- Infos trajet (distance + durée) -->
                <div class="ntb-sum-route" data-ntb-sum-route hidden>
                    <span class="ntb-sum-route-ico" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-9 4 18 3-9h4"/></svg>
                    </span>
                    <span class="ntb-sum-route-txt" data-ntb-sum-route-txt></span>
                </div>

                <!-- Bouton CTA + Retour -->
                <div class="ntb-sum-bar">
                    <div role="button" tabindex="0" class="ntb-btn ntb-sum-back" data-ntb-back aria-label="{{ __('Retour') }}" style="display: none; padding: 0; border-radius: 50%; width: 44px; height: 44px; justify-content: center; align-items: center; border: 1.5px solid var(--ntb-line); background: var(--ntb-surf); transition: all 200ms ease;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </div>
                    <div role="button" tabindex="0" class="ntb-sum-cta" data-ntb-sel-cta>
                        {{ __('Choisir ce véhicule') }}
                    </div>
                </div>
                <div class="ntb-aside-handle" data-ntb-aside-handle><span class="ntb-aside-handle-bar"></span></div>

            </div><!-- /ntb-sum-card -->

        </div><!-- /ntb-step2-aside -->

        </div><!-- /ntb-step2-body -->

    @endif
</div>
@endsection