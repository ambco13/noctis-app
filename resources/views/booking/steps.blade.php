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

        <!-- EN-TÊTE ÉTAPE 2 : titre + progression -->
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
            <div class="ntb-step3-right">
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

                    {{-- Réassurance sous le carrousel — reprend les motifs de la
                         vitrine (cartes « why choose us » + séquence « routes »)
                         pour une continuité visuelle avec le reste du site.
                         .eyb / .lift viennent de ntb-marketing.css (chargé ici). --}}
                    <section class="ntb-included">
                        <div class="ntb-inc-head">
                            <p class="eyb">{{ __('Le standard Noctis') }}</p>
                            <h3 class="ntb-sec-title">{{ __('Inclus dans chaque course') }}</h3>
                            <p class="ntb-sec-sub">{{ __('Le même standard de service, quel que soit le véhicule choisi.') }}</p>
                        </div>
                        <div class="ntb-inc-grid">
                            <div class="ntb-inc lift">
                                <span class="ntb-inc-badge" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                                </span>
                                <h4>{{ __('Chauffeur professionnel') }}</h4>
                                <p>{{ __('Chauffeurs expérimentés et véhicules récents, entretenus et assurés.') }}</p>
                            </div>
                            <div class="ntb-inc lift">
                                <span class="ntb-inc-badge" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                </span>
                                <h4>{{ __('Prix fixe garanti') }}</h4>
                                <p>{{ __('Le prix affiché est le prix payé — pas de compteur, pas de majoration surprise.') }}</p>
                            </div>
                            <div class="ntb-inc lift">
                                <span class="ntb-inc-badge" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
                                </span>
                                <h4>{{ __('Suivi de vol et de train') }}</h4>
                                <p>{{ __('Votre chauffeur s\'adapte à l\'horaire réel de votre vol ou de votre train.') }}</p>
                            </div>
                            <div class="ntb-inc lift">
                                <span class="ntb-inc-badge" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                                </span>
                                <h4>{{ __('Confort à bord') }}</h4>
                                <p>{{ __('Eau minérale et chargeurs à disposition dans tous nos véhicules.') }}</p>
                            </div>
                            <div class="ntb-inc lift">
                                <span class="ntb-inc-badge" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="18"/><line x1="14" y1="14" x2="10" y2="18"/></svg>
                                </span>
                                <h4>{{ __('Annulation flexible') }}</h4>
                                <p>{{ __('Un imprévu ? Contactez-nous : nous trouvons toujours une solution.') }}</p>
                            </div>
                            <div class="ntb-inc lift">
                                <span class="ntb-inc-badge" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                                </span>
                                <h4>{{ __('Paiement sécurisé') }}</h4>
                                <p>{{ __('Carte, Apple Pay, Google Pay ou PayPal — transaction chiffrée de bout en bout.') }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="ntb-after">
                        <div class="ntb-after-head">
                            <p class="eyb">{{ __('La suite') }}</p>
                            <h3 class="ntb-sec-title">{{ __('Et après votre réservation ?') }}</h3>
                        </div>
                        <ol class="ntb-seq">
                            <li class="ntb-seq-step">
                                <span class="ntb-seq-k">{{ __('ÉTAPE') }} 01</span>
                                <h4>{{ __('Confirmation immédiate') }}</h4>
                                <p>{{ __('Vous recevez le récapitulatif complet de votre course par email dès le paiement.') }}</p>
                            </li>
                            <li class="ntb-seq-step">
                                <span class="ntb-seq-k">{{ __('ÉTAPE') }} 02</span>
                                <h4>{{ __('Votre chauffeur se présente') }}</h4>
                                <p>{{ __('Nom du chauffeur et véhicule communiqués avant la prise en charge.') }}</p>
                            </li>
                            <li class="ntb-seq-step">
                                <span class="ntb-seq-k">{{ __('ÉTAPE') }} 03</span>
                                <h4>{{ __('Voyagez l\'esprit libre') }}</h4>
                                <p>{{ __('Tout est réglé d\'avance — rien à payer à bord, aucune mauvaise surprise.') }}</p>
                            </li>
                        </ol>
                    </section>
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
                            <label for="ntb-phone-num">{{ __('Téléphone') }}</label>
                            @include('partials.phone-input', ['id' => 'ntb-phone', 'name' => 'ntb_phone', 'value' => '', 'required' => true])
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

                        @guest
                            <!-- Signale la création automatique d'un compte (invité sans compte existant).
                                 Masqué par défaut ; révélé en JS si l'email saisi n'a pas encore de compte. -->
                            <p class="ntb-account-note" data-ntb-account-note hidden>
                                <span class="ntb-account-note-star" aria-hidden="true">*</span>
                                {{ __('Un compte sera créé pour suivre vos réservations.') }}
                            </p>
                        @endguest

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