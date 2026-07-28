@extends('layouts.admin')

@section('title', __('Réglages'))

@section('content')
<h1 class="ntb-page-title">{{ __('Réglages') }}</h1>

@php
    $tabs = [
        'general' => __('Général'),
        'tarifs' => __('Tarifs'),
        'api' => __('API & Intégrations'),
        'notifications' => __('Notifications'),
        'style' => __('Style'),
    ];
    if (! array_key_exists($tab, $tabs)) { $tab = 'general'; }
@endphp

<style>
    .nadm-tabs { display: flex; gap: 4px; margin-bottom: 18px; border-bottom: 2px solid #e5e7eb; }
    .nadm-tabs a { padding: 9px 16px; font-size: 14px; color: #6b7280; text-decoration: none; border-radius: 8px 8px 0 0; }
    .nadm-tabs a.active { background: #fff; color: #111827; font-weight: 600; border: 2px solid #e5e7eb; border-bottom: 2px solid #fff; margin-bottom: -2px; }
</style>

<div class="nadm-tabs">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.settings', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- ══════════ GÉNÉRAL ══════════ --}}
@if ($tab === 'general')
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form">
    @csrf
    <input type="hidden" name="_tab" value="general">
    <h2 style="margin-top:0;">{{ __('Général') }}</h2>
    <p style="font-size:12px;color:#6b7280;">
        {{ __('Pages du tunnel (fixes, plus besoin de shortcodes) :') }}
        <span class="nadm-code">{{ route('booking.form') }}</span> {{ __('(formulaire)') }} ·
        <span class="nadm-code">{{ route('booking.steps') }}</span> {{ __('(étapes 2-4)') }} ·
        <span class="nadm-code">{{ route('account') }}</span> {{ __('(espace client)') }}
    </p>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
        <div><label>{{ __('Devise (ISO)') }}</label><input type="text" name="currency" value="{{ $values['currency'] }}"></div>
        <div><label>{{ __('Symbole') }}</label><input type="text" name="currency_symbol" value="{{ $values['currency_symbol'] }}"></div>
        <div><label>{{ __('Indicatif pays') }}</label><input type="text" name="default_country_code" value="{{ $values['default_country_code'] }}"></div>
        <div><label>{{ __('Format date') }}</label><input type="text" name="date_format" value="{{ $values['date_format'] }}"></div>
        <div><label>{{ __('Format heure') }}</label><input type="text" name="time_format" value="{{ $values['time_format'] }}"></div>
    </div>
    <label style="display:flex;gap:10px;align-items:center;margin-top:18px;">
        <input type="checkbox" class="nadm-sw" name="aside_detach_body" value="1" @checked($values['aside_detach_body'])>
        {{ __('Panneau latéral ancré au body') }}
    </label>
    <p style="font-size:12px;color:#6b7280;margin:4px 0 0;">
        {{ __("Déplace le panneau carte/véhicule hors du conteneur de réservation et l'ancre directement au body. Garantit une position fixe au défilement quelle que soit la structure de la page.") }}
    </p>
    <div style="margin-top:20px;"><button class="nadm-btn" type="submit">{{ __('Enregistrer') }}</button></div>
</form>
@endif

{{-- ══════════ TARIFS ══════════ --}}
@if ($tab === 'tarifs')
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form">
    @csrf
    <input type="hidden" name="_tab" value="tarifs">
    <h2 style="margin-top:0;">{{ __('Majorations') }}</h2>
    <p style="font-size:12px;color:#6b7280;">{{ __('Les tarifs de base (prise en charge, €/km, €/min, minimum) se règlent par véhicule dans l\'onglet Véhicules.') }}</p>

    <label style="display:flex;gap:8px;align-items:center;">
        <input type="checkbox" class="nadm-sw" name="surcharge_night_enabled" value="1" @checked($values['surcharge_night_enabled'])>
        {{ __('Tarif nuit') }}
    </label>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
        <div><label>{{ __('Début') }}</label><input type="time" name="surcharge_night_start" value="{{ $values['surcharge_night_start'] ?: '22:00' }}"></div>
        <div><label>{{ __('Fin') }}</label><input type="time" name="surcharge_night_end" value="{{ $values['surcharge_night_end'] ?: '06:00' }}"></div>
        <div><label>%</label><input type="number" step="0.1" min="0" name="surcharge_night_pct" value="{{ $values['surcharge_night_pct'] ?: 25 }}"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
        <label style="display:flex;gap:8px;align-items:center;">
            <input type="checkbox" class="nadm-sw" name="surcharge_weekend_enabled" value="1" @checked($values['surcharge_weekend_enabled'])>
            {{ __('Week-end (%)') }}
            <input type="number" step="0.1" min="0" name="surcharge_weekend_pct" value="{{ $values['surcharge_weekend_pct'] ?: 20 }}" style="max-width:110px;">
        </label>
        <label style="display:flex;gap:8px;align-items:center;">
            <input type="checkbox" class="nadm-sw" name="surcharge_holiday_enabled" value="1" @checked($values['surcharge_holiday_enabled'])>
            {{ __('Jours fériés (%)') }}
            <input type="number" step="0.1" min="0" name="surcharge_holiday_pct" value="{{ $values['surcharge_holiday_pct'] ?: 30 }}" style="max-width:110px;">
        </label>
    </div>
    <div style="margin-top:20px;"><button class="nadm-btn" type="submit">{{ __('Enregistrer') }}</button></div>
</form>
@endif

{{-- ══════════ API & INTÉGRATIONS ══════════ --}}
@if ($tab === 'api')
@php $testMode = (string) \App\Support\Settings::get('test_mode', '1') === '1'; @endphp
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form" autocomplete="off">
    @csrf
    <input type="hidden" name="_tab" value="api">

    <div class="nadm-notice">
        <label style="display:flex;gap:10px;align-items:center;font-weight:600;">
            <input type="checkbox" class="nadm-sw" name="test_mode" value="1" @checked($testMode)>
            {{ __('Mode test (sandbox)') }}
        </label>
        {{ __('Quand activé, Stripe et PayPal utilisent les clés de test. Désactivez pour passer en production.') }}
    </div>

    <p style="font-size:12px;color:#6b7280;">
        {{ __('Les clés sont chiffrées en base (jamais affichées en clair). Champ vide = clé inchangée ; saisir « - » pour effacer la valeur admin et revenir au .env.') }}
    </p>

    @php
        $groups = [
            __('Google Maps Platform') => [
                'fields' => ['google_maps_key' => __('Clé API')],
                'test' => 'google',
                'hint' => __("Active les API Routes et Places. La clé reste côté serveur et n'est jamais exposée au navigateur."),
            ],
            __('Stripe') => [
                'fields' => [
                    'stripe_key' => __('Clé publiable (test)'),
                    'stripe_secret' => __('Clé secrète (test)'),
                    'stripe_key_live' => __('Clé publiable (live)'),
                    'stripe_secret_live' => __('Clé secrète (live)'),
                    'stripe_webhook_secret' => __('Secret du webhook'),
                ],
                'test' => 'stripe',
                'hint' => __('URL du webhook à déclarer chez Stripe :'),
            ],
            __('PayPal') => [
                'fields' => [
                    'paypal_client_id' => __('Client ID (sandbox)'),
                    'paypal_secret' => __('Secret (sandbox)'),
                    'paypal_client_id_live' => __('Client ID (live)'),
                    'paypal_secret_live' => __('Secret (live)'),
                ],
                'test' => 'paypal',
                'hint' => '',
            ],
            __('Twilio (SMS)') => [
                'fields' => [
                    'twilio_sid' => __('Account SID'),
                    'twilio_token' => __('Auth Token'),
                    'twilio_from' => __('Numéro d\'envoi (format international)'),
                ],
                'test' => 'twilio',
                'hint' => '',
            ],
        ];
    @endphp

    @foreach ($groups as $groupLabel => $group)
        <div class="nadm-test-group" data-test-group="{{ $group['test'] }}">
        <h3 style="margin-bottom:4px;">{{ $groupLabel }}</h3>
        @foreach ($group['fields'] as $key => $label)
            <label>{{ $label }}
                @if ($secrets[$key] !== '')
                    <span style="font-weight:400;color:#059669;font-size:12px;"> — {{ $secrets[$key] }}</span>
                @else
                    <span style="font-weight:400;color:#9ca3af;font-size:12px;"> — {{ __('non configurée') }}</span>
                @endif
            </label>
            <input type="text" name="apikey_{{ $key }}" data-field-key="{{ $key }}" value="" placeholder="{{ __('Nouvelle valeur…') }}" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-bwignore readonly onfocus="this.removeAttribute('readonly')">
        @endforeach
        @if ($group['hint'] !== '')
            <p style="font-size:12px;color:#6b7280;margin:6px 0 0;">{!! nl2br(e($group['hint'])) !!}
                @if (str_contains($group['hint'], 'webhook'))
                    <span class="nadm-code">{{ url('/api/v1/webhook/stripe') }}</span>
                @endif
            </p>
        @endif
        <div style="margin-top:6px;">
            <button type="button" class="nadm-test-btn" data-test-service="{{ $group['test'] }}">{{ __('Tester') }}</button>
            <span class="nadm-test-result" data-test-result="{{ $group['test'] }}"></span>
        </div>
        </div>
    @endforeach

    <div style="margin-top:20px;"><button class="nadm-btn" type="submit">{{ __('Enregistrer les clés') }}</button></div>
</form>

<script>
    // Boutons « Tester la connexion » : teste en priorité la valeur tapée à
    // l'écran (pas encore enregistrée), sinon la clé déjà enregistrée.
    document.querySelectorAll('[data-test-service]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var service = btn.getAttribute('data-test-service');
            var out = document.querySelector('[data-test-result="' + service + '"]');
            var group = document.querySelector('[data-test-group="' + service + '"]');
            var values = {};
            group.querySelectorAll('[data-field-key]').forEach(function (input) {
                if (input.value.trim() !== '') {
                    values[input.getAttribute('data-field-key')] = input.value.trim();
                }
            });
            out.textContent = '…';
            out.style.color = '#6b7280';
            fetch(@json(route('admin.settings.test')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ service: service, values: values })
            }).then(function (r) { return r.json(); }).then(function (res) {
                out.textContent = res.message;
                out.style.color = res.ok ? '#059669' : '#b91c1c';
            }).catch(function () {
                out.textContent = @json(__('Erreur réseau.'));
                out.style.color = '#b91c1c';
            });
        });
    });
</script>
@endif

{{-- ══════════ NOTIFICATIONS ══════════ --}}
@if ($tab === 'notifications')
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form">
    @csrf
    <input type="hidden" name="_tab" value="notifications">
    <h2 style="margin-top:0;">{{ __('Notifications') }}</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" class="nadm-sw" name="notif_email_customer" value="1" @checked($values['notif_email_customer'])> {{ __('Email client') }}</label>
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" class="nadm-sw" name="notif_email_admin" value="1" @checked($values['notif_email_admin'])> {{ __('Email admin') }}</label>
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" class="nadm-sw" name="notif_sms_customer" value="1" @checked($values['notif_sms_customer'])> {{ __('SMS client') }}</label>
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" class="nadm-sw" name="notif_sms_admin" value="1" @checked($values['notif_sms_admin'])> {{ __('SMS admin') }}</label>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><label>{{ __('Email admin à notifier') }}</label><input type="email" name="admin_notify_email" value="{{ $values['admin_notify_email'] }}"></div>
        <div><label>{{ __('Téléphone admin à notifier') }}</label><input type="text" name="admin_notify_phone" value="{{ $values['admin_notify_phone'] }}"></div>
    </div>

    <h2>{{ __('Templates') }}</h2>
    <p style="font-size:12px;color:#6b7280;">
        {{ __('Variables : {nom} {trajet} {date} {heure} {vehicule} {prix} {numero_reservation} {email} {telephone} {message}') }}
    </p>
    <label>{{ __('Sujet email client') }}</label>
    <input type="text" name="tpl_email_customer_subject" value="{{ $values['tpl_email_customer_subject'] }}">
    <label>{{ __('Corps email client') }}</label>
    <textarea name="tpl_email_customer_body" rows="5">{{ $values['tpl_email_customer_body'] }}</textarea>
    <label>{{ __('Sujet email admin') }}</label>
    <input type="text" name="tpl_email_admin_subject" value="{{ $values['tpl_email_admin_subject'] }}">
    <label>{{ __('Corps email admin') }}</label>
    <textarea name="tpl_email_admin_body" rows="5">{{ $values['tpl_email_admin_body'] }}</textarea>
    <label>{{ __('SMS client') }}</label>
    <textarea name="tpl_sms_customer" rows="2">{{ $values['tpl_sms_customer'] }}</textarea>
    <label>{{ __('SMS admin') }}</label>
    <textarea name="tpl_sms_admin" rows="2">{{ $values['tpl_sms_admin'] }}</textarea>

    <div style="margin-top:20px;"><button class="nadm-btn" type="submit">{{ __('Enregistrer') }}</button></div>
</form>
@endif

{{-- ══════════ STYLE ══════════ --}}
@if ($tab === 'style')
@php
    $presets = \App\Support\Design::PRESETS;
    $fonts = \App\Support\Design::FONTS;
    $saved = \App\Support\Design::saved();
    $advKeys = ['--ntb-accent-hi', '--ntb-surf', '--ntb-surf2', '--ntb-line', '--ntb-muted', '--ntb-faint'];
    $advOn = (bool) array_intersect_key($saved, array_flip($advKeys));
    $cardRadius = (int) $design['--ntb-r'];
    $btnRadius = min(100, (int) $design['ntb_btn_radius']);
    $blurPx = (int) preg_replace('/\D/', '', $design['--ntb-glass-blur']);
    $themeMode = \App\Support\Design::themeMode();
    $csSuggest = [
        '.ntb-btn', '.ntb-btn-primary', '.ntb-back-btn',
        '.ntb-field', '.ntb-field-input', '.ntb-field-icon', '.ntb-field-textarea', '.ntb-field-err',
        '.ntb-ac-list', '.ntb-ac-item', '.ntb-ac-txt',
        '.ntb-home', '.ntb-home-form', '.ntb-hf-grid', '.ntb-hf-go',
        '.ntb-hero-title', '.ntb-hero-tagline', '.ntb-hero-eyebrow',
        '.ntb-step', '.ntb-step-title', '.ntb-prog', '.ntb-prog-node', '.ntb-prog-bub',
        '.ntb-prog-lab', '.ntb-prog-line',
        '.ntb-sum-card', '.ntb-sum-header', '.ntb-sum-title', '.ntb-sum-subtitle',
        '.ntb-sum-price', '.ntb-sum-route', '.ntb-sum-cta', '.ntb-sum-bar',
        '.ntb-veh-carousel', '.ntb-veh-arrow', '.ntb-vcard', '.ntb-vc-photo', '.ntb-vc-body',
        '.ntb-vc-name', '.ntb-vc-price', '.ntb-vc-specs', '.ntb-vc-ico',
        '.ntb-included', '.ntb-sec-title', '.ntb-inc', '.ntb-inc-badge', '.ntb-after', '.ntb-seq', '.ntb-seq-step',
        '.ntb-step3-form', '.ntb-pay-choice', '.ntb-pay-tab',
        '.ntb-pay-panel', '.ntb-pay-btn', '.ntb-pay-bar', '.ntb-pay-back',
        '.ntb-recap', '.ntb-recap-title', '.ntb-recap-route', '.ntb-recap-total', '.ntb-recap-amount',
        '.ntb-confirm-icon', '.ntb-confirm-title', '.ntb-confirm-ref', '.ntb-confirm-note',
        '.ntb-dashboard', '.ntb-booking-card', '.ntb-booking-ref', '.ntb-booking-price', '.ntb-status',
    ];
@endphp
<style>
    .nst-panel { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; margin-bottom: 18px; }
    .nst-panel-head { padding: 12px 18px; border-bottom: 1px solid #eef0f3; font-weight: 700; font-size: 14px; }
    .nst-panel-body { padding: 16px 18px; }
    .nst-row { display: grid; grid-template-columns: 180px 1fr; gap: 14px; padding: 10px 0; align-items: start; }
    .nst-row + .nst-row { border-top: 1px solid #f3f4f6; }
    .nst-row > label:first-child, .nst-row > span:first-child { font-weight: 600; font-size: 13px; padding-top: 6px; }
    .nst-desc { font-size: 12px; color: #6b7280; margin: 5px 0 0; }
    .nst-color { display: inline-flex; align-items: center; gap: 8px; }
    .nst-color input[type=color] { width: 46px; height: 30px; padding: 2px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; }
    .nst-color input[type=text] { width: 240px; font-family: ui-monospace, monospace; font-size: 12px; }
    .nst-range { display: flex; align-items: center; gap: 12px; }
    .nst-range input[type=range] { width: 200px; }
    .nst-range .nst-range-label { min-width: 110px; font-size: 12px; color: #6b7280; }
    .nst-presets { display: flex; flex-wrap: wrap; gap: 10px; }
    .nst-preset { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border: 1px solid #d1d5db; border-radius: 7px; background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; }
    .nst-preset:hover { border-color: #2563eb; color: #2563eb; }
    .nst-preset .dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    .nst-trans { margin-top: 10px; font-size: 13px; }
    .nst-trans-rows { margin-top: 10px; }
    .nst-adv[data-on="0"] .nst-adv-dim { opacity: .45; }
    #ntb-cs-preview { padding: 18px; border: 1px solid #dcdcde; border-radius: 8px; background: #1b1f27; display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-start; min-height: 60px; }
    #ntb-cs-preview .ntb-cs-prev-el { display: inline-block; padding: 10px 16px; border: 1px solid #474e58; border-radius: 8px; background: #2b3038; color: #f4f5f8; font-size: 14px; font-family: system-ui, sans-serif; line-height: 1.3; }
    #ntb-cs-preview input.ntb-cs-prev-el, #ntb-cs-preview textarea.ntb-cs-prev-el { width: 170px; }
    #ntb-cs-preview .ntb-cs-prev-item { display: flex; flex-direction: column; gap: 5px; }
    #ntb-cs-preview .ntb-cs-prev-sel { font-size: 11px; color: #9aa0a6; font-family: monospace; }
    .ntb-cs-item { border: 1px solid #dcdcde; border-radius: 8px; margin-bottom: 10px; background: #fff; }
    .ntb-cs-head { display: flex; align-items: center; gap: 10px; padding: 10px 14px; cursor: pointer; background: #f6f7f7; border-radius: 8px 8px 0 0; }
    .ntb-cs-head code { flex: 1; font-size: 13px; }
    .ntb-cs-head button { background: none; border: none; cursor: pointer; font-size: 14px; padding: 2px 4px; }
    .ntb-cs-body { padding: 14px; }
</style>

{{-- ── Formulaire principal du style ── --}}
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form" id="nst-form">
    @csrf
    <input type="hidden" name="_tab" value="style">
    <h2 style="margin-top:0;">{{ __('Style du tunnel & de l\'espace client') }}</h2>

    <div class="nst-panel">
        <div class="nst-panel-head">{{ __('Présets') }}</div>
        <div class="nst-panel-body nst-presets">
            @foreach ($presets as $preset)
                <button type="button" class="nst-preset"
                    data-accent="{{ $preset['accent'] }}" data-bg="{{ $preset['bg'] }}"
                    data-text="{{ $preset['text'] }}" data-danger="{{ $preset['danger'] }}">
                    <span class="dot" style="background:{{ $preset['accent'] }}"></span>{{ $preset['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="nst-panel">
        <div class="nst-panel-head">{{ __('Mode d\'affichage') }}</div>
        <div class="nst-panel-body">
            <div class="nst-row">
                <span>{{ __('Thème UI') }}</span>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:7px;margin-right:20px;">
                        <input type="radio" name="design[ntb_theme_mode]" value="dark" @checked($themeMode === 'dark')> 🌙 {{ __('Sombre') }}
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:7px;margin-right:20px;">
                        <input type="radio" name="design[ntb_theme_mode]" value="light" @checked($themeMode === 'light')> ☀️ {{ __('Clair') }}
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:7px;">
                        <input type="radio" name="design[ntb_theme_mode]" value="auto" @checked($themeMode === 'auto')> ⚙️ {{ __('Auto — suit la préférence système (défaut)') }}
                    </label>
                    <p class="nst-desc">{{ __('Sombre/Clair force le même thème pour tous les visiteurs. Auto suit la préférence de chaque visiteur.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="nst-panel">
        <div class="nst-panel-head">{{ __('Couleurs') }}</div>
        <div class="nst-panel-body">
            <p class="nst-desc" style="margin:0 0 6px;">{{ __('Choisissez au sélecteur visuel ou tapez un code CSS (hex, oklch, color-mix…) — les deux restent synchronisés.') }}</p>
            <div class="nst-row">
                <label for="nst-accent">{{ __('Couleur d\'accent') }}</label>
                <div>
                    <span class="nst-color"><input type="color" data-sync="nst-accent"><input type="text" id="nst-accent" name="design[--ntb-accent]" value="{{ $design['--ntb-accent'] }}"></span>
                    <p class="nst-desc">{{ __('Couleur principale des boutons, liens et éléments actifs.') }}</p>
                </div>
            </div>
            <div class="nst-row">
                <label for="nst-bg">{{ __('Fond principal') }}</label>
                <div>
                    <span class="nst-color"><input type="color" data-sync="nst-bg"><input type="text" id="nst-bg" name="design[--ntb-bg]" value="{{ $design['--ntb-bg'] }}"></span>
                    <p class="nst-desc">{{ __('Fond global du tunnel de réservation. Les surfaces, bordures et nuances de texte en sont dérivées automatiquement.') }}</p>
                    <div class="nst-trans">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="hidden" name="design[ntb_trans_bg]" value="0">
                            <input type="checkbox" name="design[ntb_trans_bg]" value="1" @checked($design['ntb_trans_bg'] === '1')>
                            {{ __('Rendre transparent') }}
                        </label>
                        <p class="nst-desc">{{ __('Le conteneur devient totalement transparent. Le flou reste réglable via « Effet verre » ci-dessous.') }}</p>
                    </div>
                </div>
            </div>
            <div class="nst-row">
                <label for="nst-text">{{ __('Couleur du texte') }}</label>
                <div><span class="nst-color"><input type="color" data-sync="nst-text"><input type="text" id="nst-text" name="design[--ntb-text]" value="{{ $design['--ntb-text'] }}"></span></div>
            </div>
            <div class="nst-row">
                <label for="nst-on-accent">{{ __('Texte sur accent') }}</label>
                <div>
                    <span class="nst-color"><input type="color" data-sync="nst-on-accent"><input type="text" id="nst-on-accent" name="design[--ntb-on-accent]" value="{{ $design['--ntb-on-accent'] }}"></span>
                    <p class="nst-desc">{{ __('Couleur du texte sur les boutons d\'accent (« Estimer », « Choisir ce véhicule », « Payer »).') }}</p>
                </div>
            </div>
            <div class="nst-row">
                <label for="nst-danger">{{ __('Couleur d\'erreur') }}</label>
                <div><span class="nst-color"><input type="color" data-sync="nst-danger"><input type="text" id="nst-danger" name="design[--ntb-danger]" value="{{ $design['--ntb-danger'] }}"></span></div>
            </div>
            <div class="nst-row">
                <label for="nst-field-line">{{ __('Trait sous les champs') }}</label>
                <div>
                    <span class="nst-color"><input type="color" data-sync="nst-field-line"><input type="text" id="nst-field-line" name="design[--ntb-field-line]" value="{{ $design['--ntb-field-line'] }}"></span>
                    <p class="nst-desc">{{ __('Couleur du trait sous chaque champ du formulaire (Départ, Destination, Date, Heure). Blanc par défaut.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="nst-panel nst-adv" data-on="{{ $advOn ? '1' : '0' }}">
        <div class="nst-panel-head">{{ __('Couleurs avancées') }}</div>
        <div class="nst-panel-body">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                <input type="checkbox" id="nst-adv-toggle" @checked($advOn)>
                {{ __('Personnaliser les surfaces, bordures et nuances de texte') }}
            </label>
            <p class="nst-desc">{{ __('Désactivé, ces couleurs sont calculées automatiquement à partir du fond et de l\'accent (recommandé). Activez pour un contrôle précis de chaque élément.') }}</p>

            <div class="nst-adv-dim">
                <div class="nst-row">
                    <label for="nst-accent-hi">{{ __('Accent — survol') }}</label>
                    <div>
                        <span class="nst-color"><input type="color" data-sync="nst-accent-hi" class="nst-adv-input"><input type="text" id="nst-accent-hi" name="design[--ntb-accent-hi]" value="{{ $design['--ntb-accent-hi'] }}" class="nst-adv-input"></span>
                        <p class="nst-desc">{{ __('Teinte de l\'accent au survol des boutons et liens.') }}</p>
                    </div>
                </div>
                <div class="nst-row">
                    <label for="nst-surf">{{ __('Surface / cartes') }}</label>
                    <div>
                        <span class="nst-color"><input type="color" data-sync="nst-surf" class="nst-adv-input"><input type="text" id="nst-surf" name="design[--ntb-surf]" value="{{ $design['--ntb-surf'] }}" class="nst-adv-input"></span>
                        <p class="nst-desc">{{ __('Fond des cartes, panneaux, champs et du récapitulatif.') }}</p>
                        <div class="nst-trans">
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="hidden" name="design[ntb_trans_surf]" value="0">
                                <input type="checkbox" name="design[ntb_trans_surf]" value="1" data-rows="nst-trans-surf" @checked($design['ntb_trans_surf'] === '1')>
                                {{ __('Rendre transparent') }}
                            </label>
                            <div class="nst-trans-rows" id="nst-trans-surf" @style(['display:none' => $design['ntb_trans_surf'] !== '1'])>
                                <div class="nst-range"><span class="nst-range-label">{{ __('Opacité') }}</span>
                                    <input type="range" name="design[ntb_alpha_surf]" min="0" max="100" value="{{ (int) $design['ntb_alpha_surf'] }}" oninput="this.nextElementSibling.value=this.value+'%'"><output>{{ (int) $design['ntb_alpha_surf'] }}%</output></div>
                                <div class="nst-range"><span class="nst-range-label">{{ __('Flou') }}</span>
                                    <input type="range" name="design[ntb_blur_surf]" min="0" max="20" value="{{ (int) $design['ntb_blur_surf'] }}" oninput="this.nextElementSibling.value=this.value+'px'"><output>{{ (int) $design['ntb_blur_surf'] }}px</output></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nst-row">
                    <label for="nst-surf2">{{ __('Surface — survol') }}</label>
                    <div>
                        <span class="nst-color"><input type="color" data-sync="nst-surf2" class="nst-adv-input"><input type="text" id="nst-surf2" name="design[--ntb-surf2]" value="{{ $design['--ntb-surf2'] }}" class="nst-adv-input"></span>
                        <p class="nst-desc">{{ __('Fond des éléments survolés et des onglets/segments secondaires.') }}</p>
                        <div class="nst-trans">
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="hidden" name="design[ntb_trans_surf2]" value="0">
                                <input type="checkbox" name="design[ntb_trans_surf2]" value="1" data-rows="nst-trans-surf2" @checked($design['ntb_trans_surf2'] === '1')>
                                {{ __('Rendre transparent') }}
                            </label>
                            <div class="nst-trans-rows" id="nst-trans-surf2" @style(['display:none' => $design['ntb_trans_surf2'] !== '1'])>
                                <div class="nst-range"><span class="nst-range-label">{{ __('Opacité') }}</span>
                                    <input type="range" name="design[ntb_alpha_surf2]" min="0" max="100" value="{{ (int) $design['ntb_alpha_surf2'] }}" oninput="this.nextElementSibling.value=this.value+'%'"><output>{{ (int) $design['ntb_alpha_surf2'] }}%</output></div>
                                <div class="nst-range"><span class="nst-range-label">{{ __('Flou') }}</span>
                                    <input type="range" name="design[ntb_blur_surf2]" min="0" max="20" value="{{ (int) $design['ntb_blur_surf2'] }}" oninput="this.nextElementSibling.value=this.value+'px'"><output>{{ (int) $design['ntb_blur_surf2'] }}px</output></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nst-row">
                    <label for="nst-line">{{ __('Bordures') }}</label>
                    <div>
                        <span class="nst-color"><input type="color" data-sync="nst-line" class="nst-adv-input"><input type="text" id="nst-line" name="design[--ntb-line]" value="{{ $design['--ntb-line'] }}" class="nst-adv-input"></span>
                        <p class="nst-desc">{{ __('Couleur des contours, séparateurs et bordures de champs.') }}</p>
                        <div class="nst-trans">
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="hidden" name="design[ntb_trans_line]" value="0">
                                <input type="checkbox" name="design[ntb_trans_line]" value="1" data-rows="nst-trans-line" @checked($design['ntb_trans_line'] === '1')>
                                {{ __('Rendre transparent') }}
                            </label>
                            <div class="nst-trans-rows" id="nst-trans-line" @style(['display:none' => $design['ntb_trans_line'] !== '1'])>
                                <div class="nst-range"><span class="nst-range-label">{{ __('Opacité') }}</span>
                                    <input type="range" name="design[ntb_alpha_line]" min="0" max="100" value="{{ (int) $design['ntb_alpha_line'] }}" oninput="this.nextElementSibling.value=this.value+'%'"><output>{{ (int) $design['ntb_alpha_line'] }}%</output></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nst-row">
                    <label for="nst-muted">{{ __('Texte secondaire') }}</label>
                    <div>
                        <span class="nst-color"><input type="color" data-sync="nst-muted" class="nst-adv-input"><input type="text" id="nst-muted" name="design[--ntb-muted]" value="{{ $design['--ntb-muted'] }}" class="nst-adv-input"></span>
                        <p class="nst-desc">{{ __('Sous-titres, libellés et descriptions atténuées.') }}</p>
                    </div>
                </div>
                <div class="nst-row">
                    <label for="nst-faint">{{ __('Texte discret') }}</label>
                    <div>
                        <span class="nst-color"><input type="color" data-sync="nst-faint" class="nst-adv-input"><input type="text" id="nst-faint" name="design[--ntb-faint]" value="{{ $design['--ntb-faint'] }}" class="nst-adv-input"></span>
                        <p class="nst-desc">{{ __('Mentions très secondaires (libellés du récapitulatif, indices).') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="nst-panel">
        <div class="nst-panel-head">{{ __('Typographie & Géométrie') }}</div>
        <div class="nst-panel-body">
            <div class="nst-row">
                <label for="nst-sans">{{ __('Police principale') }}</label>
                <div>
                    <select id="nst-sans" name="design[--ntb-sans]" style="max-width:280px;">
                        @foreach ($fonts as $val => $label)
                            <option value="{{ $val }}" @selected($design['--ntb-sans'] === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nst-row">
                <label for="nst-r">{{ __('Arrondi des coins') }}</label>
                <div>
                    <div class="nst-range">
                        <input type="range" id="nst-r" min="0" max="24" value="{{ $cardRadius }}"
                            oninput="this.nextElementSibling.value=this.value+'px';['nst-r-h','nst-r-sm','nst-r-lg'].forEach(i=>document.getElementById(i).value=this.value+'px')"><output>{{ $cardRadius }}px</output>
                    </div>
                    <input type="hidden" id="nst-r-h" name="design[--ntb-r]" value="{{ $design['--ntb-r'] }}">
                    <input type="hidden" id="nst-r-sm" name="design[--ntb-r-sm]" value="{{ $design['--ntb-r-sm'] }}">
                    <input type="hidden" id="nst-r-lg" name="design[--ntb-r-lg]" value="{{ $design['--ntb-r-lg'] }}">
                </div>
            </div>
            <div class="nst-row">
                <label for="nst-btn-r">{{ __('Arrondi des boutons') }}</label>
                <div>
                    <div class="nst-range">
                        <input type="range" id="nst-btn-r" name="design[ntb_btn_radius]" min="0" max="100" value="{{ $btnRadius }}"
                            oninput="this.nextElementSibling.value=this.value+'px'"><output>{{ $btnRadius }}px</output>
                    </div>
                    <p class="nst-desc">{{ __('Arrondi des coins des boutons (Estimer, Payer, Choisir un véhicule…). 0 = carré, 100 = pilule.') }}</p>
                </div>
            </div>
            <div class="nst-row">
                <label for="nst-dur">{{ __('Vitesse des animations') }}</label>
                <div>
                    <select id="nst-dur" name="design[--ntb-dur]" style="max-width:280px;">
                        <option value="0ms" @selected($design['--ntb-dur'] === '0ms')>{{ __('Aucune (accessibilité)') }}</option>
                        <option value="140ms" @selected($design['--ntb-dur'] === '140ms')>{{ __('Rapide') }}</option>
                        <option value="220ms" @selected($design['--ntb-dur'] === '220ms')>{{ __('Standard (défaut)') }}</option>
                        <option value="380ms" @selected($design['--ntb-dur'] === '380ms')>{{ __('Lente et fluide') }}</option>
                    </select>
                </div>
            </div>
            <div class="nst-row">
                <span>{{ __('Effet verre') }}</span>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="hidden" name="design[ntb_glass_enabled]" value="0">
                        <input type="checkbox" id="nst-glass" name="design[ntb_glass_enabled]" value="1" @checked($design['ntb_glass_enabled'] !== '0')>
                        {{ __('Activer l\'effet verre (glassmorphism)') }}
                    </label>
                    <div id="nst-glass-rows" style="margin-top:12px;" @style(['display:none' => $design['ntb_glass_enabled'] === '0'])>
                        <div class="nst-range"><span class="nst-range-label">{{ __('Opacité du fond') }}</span>
                            <input type="range" name="design[ntb_glass_opacity]" min="50" max="100" value="{{ (int) $design['ntb_glass_opacity'] }}" oninput="this.nextElementSibling.value=this.value+'%'"><output>{{ (int) $design['ntb_glass_opacity'] }}%</output></div>
                        <div class="nst-range"><span class="nst-range-label">{{ __('Intensité du flou') }}</span>
                            <input type="range" id="nst-glass-blur" min="0" max="20" value="{{ $blurPx }}"
                                oninput="this.nextElementSibling.value=this.value+'px';document.getElementById('nst-glass-blur-h').value='blur('+this.value+'px)'"><output>{{ $blurPx }}px</output></div>
                        <input type="hidden" id="nst-glass-blur-h" name="design[--ntb-glass-blur]" value="{{ $design['--ntb-glass-blur'] }}">
                    </div>
                    <p class="nst-desc">{{ __('Fond semi-transparent des éléments flottants. Réduire l\'opacité renforce l\'effet verre.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div style="margin:16px 0 26px;display:flex;gap:10px;">
        <button class="nadm-btn" type="submit">{{ __('Enregistrer le style') }}</button>
        <button class="nadm-btn nadm-btn--danger" type="submit" name="_action" value="reset"
                onclick="return confirm(@json(__('Réinitialiser le style aux valeurs par défaut ?')));">
            {{ __('Réinitialiser') }}
        </button>
        <a class="nadm-btn nadm-btn--light" href="{{ route('booking.form') }}" target="_blank">{{ __('Voir le site') }}</a>
    </div>
</form>

{{-- ── Styles personnalisés (éditeur libre par sélecteur) ── --}}
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form" id="ntb-cs-form">
    @csrf
    <input type="hidden" name="_tab" value="style">
    <input type="hidden" name="_action" value="save_custom">
    <input type="hidden" name="custom_styles_json" id="ntb-cs-json" value="">

    <div class="nst-panel">
        <div class="nst-panel-head">{{ __('Styles personnalisés (par classe)') }}</div>
        <div class="nst-panel-body">
            <p class="nst-desc" style="margin:0 0 14px;">
                {{ __('Ciblez n\'importe quelle classe du tunnel et surchargez son apparence complète. Les styles sont confinés au tunnel de réservation et à l\'espace client — ils n\'affectent jamais le reste du site.') }}
            </p>

            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
                <input type="text" id="ntb-cs-selector" list="ntb-cs-suggest" placeholder=".ntb-btn-primary" style="flex:1;min-width:240px;">
                <datalist id="ntb-cs-suggest">
                    @foreach ($csSuggest as $s)
                        <option value="{{ $s }}"></option>
                    @endforeach
                </datalist>
                <button type="button" class="nadm-btn nadm-btn--light" id="ntb-cs-add">+ {{ __('Ajouter') }}</button>
            </div>

            <div id="ntb-cs-list"></div>
            <p class="nst-desc" id="ntb-cs-empty" style="margin:0 0 8px;">{{ __('Aucune règle pour l\'instant. Saisissez un sélecteur ci-dessus puis cliquez « Ajouter ».') }}</p>

            <h3 style="margin:20px 0 8px;font-size:13px;">{{ __('Aperçu') }}</h3>
            <style id="ntb-cs-preview-css"></style>
            <div id="ntb-cs-preview" class="ntb-scope"></div>
            <p class="nst-desc">{{ __('L\'aperçu affiche uniquement les éléments que vous ciblez, construits à partir de chaque sélecteur. Vos surcharges s\'appliquent en direct.') }}</p>

            <details style="margin-top:16px;">
                <summary style="cursor:pointer;font-weight:600;">{{ __('CSS généré') }}</summary>
                <textarea id="ntb-cs-generated" readonly rows="8" style="width:100%;margin-top:8px;font-family:monospace;font-size:12px;background:#f6f7f7;"></textarea>
            </details>

            <details style="margin-top:10px;" @if (trim($customStyles['raw']) !== '') open @endif>
                <summary style="cursor:pointer;font-weight:600;">{{ __('CSS brut additionnel (avancé)') }}</summary>
                <p class="nst-desc" style="margin:8px 0 6px;">{{ __('Pour les règles complexes (media queries, pseudo-éléments…). Préfixez vos sélecteurs avec .ntb-scope ou .ntb-dashboard pour rester confiné.') }}</p>
                <textarea name="custom_styles_raw" id="ntb-cs-raw" rows="6" style="width:100%;font-family:monospace;font-size:12px;" placeholder=".ntb-scope .ntb-btn-primary:hover { transform: translateY(-1px); }">{{ $customStyles['raw'] }}</textarea>
            </details>

            <div style="margin-top:18px;display:flex;gap:10px;">
                <button type="submit" class="nadm-btn">{{ __('Enregistrer les styles personnalisés') }}</button>
                <button type="submit" class="nadm-btn nadm-btn--danger" name="_action" value="reset_custom"
                        onclick="return confirm(@json(__('Supprimer tous les styles personnalisés (règles par sélecteur + CSS brut) ?')));">
                    {{ __('Réinitialiser') }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    /* ── Couleurs : synchro sélecteur visuel <-> code CSS ───────────────
       Le champ texte (code) est la valeur enregistrée ; le sélecteur visuel
       est un raccourci. Tout code CSS valide (oklch, color-mix…) est résolu
       en hex sRGB via un canvas 1x1 — getComputedStyle renverrait la chaîne
       oklch telle quelle, illisible pour <input type=color>. */
    var cnv = document.createElement('canvas');
    cnv.width = cnv.height = 1;
    var cctx = cnv.getContext('2d', { willReadFrequently: true });

    function cssToHex(v) {
        if (!v || !cctx) return null;
        v = String(v).trim();
        if (!v) return null;
        try {
            cctx.fillStyle = '#010203';
            cctx.fillStyle = v;
            // Valeur invalide : fillStyle garde le témoin.
            if (cctx.fillStyle === '#010203' && v.toLowerCase() !== '#010203') return null;
            cctx.clearRect(0, 0, 1, 1);
            cctx.fillRect(0, 0, 1, 1);
            var d = cctx.getImageData(0, 0, 1, 1).data;
            if (d[3] === 0) return 'transparent';
            return '#' + [d[0], d[1], d[2]].map(function (x) {
                return ('0' + x.toString(16)).slice(-2);
            }).join('');
        } catch (e) { return null; }
    }

    document.querySelectorAll('input[type=color][data-sync]').forEach(function (picker) {
        var code = document.getElementById(picker.dataset.sync);
        if (!code) return;
        var hex = cssToHex(code.value);
        if (hex && hex[0] === '#') picker.value = hex;
        picker.addEventListener('input', function () { code.value = picker.value; });
        code.addEventListener('input', function () {
            var h = cssToHex(code.value.trim());
            if (h && h[0] === '#') picker.value = h;
        });
    });

    /* ── Présets : remplissent accent / fond / texte / erreur ── */
    document.querySelectorAll('.nst-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            [['accent', 'nst-accent'], ['bg', 'nst-bg'], ['text', 'nst-text'], ['danger', 'nst-danger']].forEach(function (p) {
                var code = document.getElementById(p[1]);
                if (!code) return;
                code.value = btn.dataset[p[0]];
                code.dispatchEvent(new Event('input'));
            });
        });
    });

    /* ── Couleurs avancées : inputs désactivés = non soumis = dérivation auto ── */
    var advToggle = document.getElementById('nst-adv-toggle');
    var advPanel = document.querySelector('.nst-adv');
    function syncAdv() {
        var on = advToggle.checked;
        advPanel.dataset.on = on ? '1' : '0';
        document.querySelectorAll('.nst-adv-input').forEach(function (i) { i.disabled = !on; });
    }
    advToggle.addEventListener('change', syncAdv);
    syncAdv();

    /* ── Transparence : afficher les curseurs quand la case est cochée ── */
    document.querySelectorAll('input[type=checkbox][data-rows]').forEach(function (chk) {
        chk.addEventListener('change', function () {
            document.getElementById(chk.dataset.rows).style.display = chk.checked ? '' : 'none';
        });
    });

    /* ── Effet verre : afficher les curseurs quand actif ── */
    var glass = document.getElementById('nst-glass');
    glass.addEventListener('change', function () {
        document.getElementById('nst-glass-rows').style.display = glass.checked ? '' : 'none';
    });

    /* ═══════════ Styles personnalisés (éditeur par sélecteur) ═══════════ */
    var init = {
        rules: @json(array_values($customStyles['rules'])),
        fonts: @json(array_keys($fonts)),
        publicCss: @json(asset('assets/css/ntb-public.css').'?v='.filemtime(public_path('assets/css/ntb-public.css'))),
        pickersCss: @json(asset('assets/css/ntb-pickers.css').'?v='.filemtime(public_path('assets/css/ntb-pickers.css')))
    };
    var listEl = document.getElementById('ntb-cs-list');
    var emptyEl = document.getElementById('ntb-cs-empty');
    var genEl = document.getElementById('ntb-cs-generated');
    var prevEl = document.getElementById('ntb-cs-preview-css');
    var prevBox = document.getElementById('ntb-cs-preview');
    var jsonEl = document.getElementById('ntb-cs-json');
    var addBtn = document.getElementById('ntb-cs-add');
    var selIn = document.getElementById('ntb-cs-selector');
    var csForm = document.getElementById('ntb-cs-form');
    var FONTS = init.fonts;

    /* Schéma des propriétés éditables (apparence complète). */
    var SCHEMA = [
        { prop: 'background-color', label: 'Fond', type: 'color', alpha: true },
        { prop: 'color', label: 'Texte', type: 'color' },
        { prop: 'border-color', label: 'Bordure (couleur)', type: 'color' },
        { prop: 'border-width', label: 'Bordure (épaisseur)', type: 'px', max: 12 },
        { prop: 'border-style', label: 'Bordure (style)', type: 'select', options: ['', 'solid', 'dashed', 'dotted', 'none'] },
        { prop: 'border-radius', label: 'Arrondi', type: 'px', max: 60 },
        { prop: 'padding', label: 'Marge interne', type: 'text', placeholder: '10px 16px' },
        { prop: 'margin', label: 'Marge externe', type: 'text', placeholder: '0 0 12px' },
        { prop: 'font-family', label: 'Police', type: 'font' },
        { prop: 'font-size', label: 'Taille texte', type: 'px', max: 64 },
        { prop: 'font-weight', label: 'Graisse', type: 'select', options: ['', '300', '400', '500', '600', '700', '800'] },
        { prop: 'box-shadow', label: 'Ombre', type: 'select', options: [
            { v: '', l: '— aucune —' },
            { v: '0 1px 2px rgba(0,0,0,.12)', l: 'Légère' },
            { v: '0 4px 12px rgba(0,0,0,.15)', l: 'Moyenne' },
            { v: '0 10px 30px rgba(0,0,0,.22)', l: 'Forte' },
            { v: 'none', l: 'Désactiver' }
        ] },
        { prop: 'opacity', label: 'Opacité', type: 'range01' }
    ];
    var KNOWN = SCHEMA.map(function (s) { return s.prop; });

    /* Brouillon local — survit à un rechargement avant enregistrement. */
    var DRAFT_KEY = 'ntb_cs_draft_v1';
    var draftRestored = false;

    function savedState() {
        return (init.rules || []).map(function (r) {
            return { selector: r.selector || '', props: r.props || {}, locked: !!r.locked };
        });
    }
    var state = savedState();
    try {
        var rawDraft = window.localStorage.getItem(DRAFT_KEY);
        if (rawDraft) {
            var draft = JSON.parse(rawDraft);
            if (draft && draft.length && JSON.stringify(draft) !== JSON.stringify(state)) {
                state = draft.map(function (r) {
                    return { selector: r.selector || '', props: r.props || {}, locked: !!r.locked };
                });
                draftRestored = true;
            }
        }
    } catch (e) {}
    function saveDraft() { try { window.localStorage.setItem(DRAFT_KEY, jsonEl.value); } catch (e) {} }
    function clearDraft() { try { window.localStorage.removeItem(DRAFT_KEY); } catch (e) {} }

    function freeProps(props) {
        var lines = [];
        Object.keys(props).forEach(function (k) {
            if (KNOWN.indexOf(k) === -1) lines.push(k + ': ' + props[k] + ';');
        });
        return lines.join('\n');
    }

    /* ── Mesure des valeurs réelles dans un iframe isolé (vrai CSS du tunnel) ── */
    var measureReady = false, measureDoc = null, measureWin = null, measureBox = null;
    function initMeasure(cb) {
        var fr = document.createElement('iframe');
        fr.style.cssText = 'position:absolute;left:-9999px;top:0;width:480px;height:320px;visibility:hidden;border:0';
        fr.setAttribute('aria-hidden', 'true');
        document.body.appendChild(fr);
        var doc = fr.contentDocument;
        doc.open();
        doc.write('<!DOCTYPE html><html><head>' +
            '<link rel="stylesheet" href="' + init.publicCss + '">' +
            '<link rel="stylesheet" href="' + init.pickersCss + '">' +
            '</head><body style="margin:0"><div id="box" class="ntb-scope ntb-dashboard"></div></body></html>');
        doc.close();
        measureDoc = doc;
        measureWin = fr.contentWindow;
        measureBox = doc.getElementById('box');
        var links = doc.querySelectorAll('link');
        var pending = links.length;
        function done() { if (--pending <= 0 && !measureReady) { measureReady = true; cb && cb(); } }
        if (!pending) { measureReady = true; cb && cb(); return; }
        links.forEach(function (l) { l.addEventListener('load', done); l.addEventListener('error', done); });
        setTimeout(function () { if (!measureReady) { measureReady = true; cb && cb(); } }, 1500);
    }

    /* Normalise une couleur calculée (rgb(), oklch()…) en hex via le canvas. */
    function rgbToHex(color) {
        return cssToHex(color) || '';
    }
    function shorthand(t, r, b, l) {
        if (t === r && r === b && b === l) return t;
        if (t === b && r === l) return t + ' ' + r;
        return t + ' ' + r + ' ' + b + ' ' + l;
    }

    /* Valeurs calculées (base) d'un élément ciblé par le sélecteur. */
    function computeBase(selector) {
        if (!measureReady || !measureBox) return {};
        measureBox.innerHTML = '';
        var el = selToEl(selector, measureDoc);
        measureBox.appendChild(el);
        var cs = measureWin.getComputedStyle(el);
        var base = {
            'background-color': rgbToHex(cs.backgroundColor),
            'color': rgbToHex(cs.color),
            'border-color': rgbToHex(cs.borderTopColor),
            'border-width': (parseInt(cs.borderTopWidth, 10) || 0) + 'px',
            'border-style': cs.borderTopStyle,
            'border-radius': (parseInt(cs.borderTopLeftRadius, 10) || 0) + 'px',
            'padding': shorthand(cs.paddingTop, cs.paddingRight, cs.paddingBottom, cs.paddingLeft),
            'margin': shorthand(cs.marginTop, cs.marginRight, cs.marginBottom, cs.marginLeft),
            'font-family': cs.fontFamily,
            'font-size': (Math.round(parseFloat(cs.fontSize)) || 0) + 'px',
            'font-weight': cs.fontWeight === 'normal' ? '400' : (cs.fontWeight === 'bold' ? '700' : cs.fontWeight),
            'box-shadow': cs.boxShadow,
            'opacity': cs.opacity
        };
        measureBox.innerHTML = '';
        return base;
    }

    function mkLabel(text) {
        var l = document.createElement('label');
        l.style.cssText = 'display:block;font-size:11px;color:#646970;margin-bottom:3px';
        l.textContent = text;
        return l;
    }

    /* Panneau de contrôles, lié directement à `rule` (closure). `base` =
       valeurs réelles calculées ; seuls les écarts à la base sont enregistrés. */
    function buildControls(rule, base) {
        base = base || {};
        var grid = document.createElement('div');
        grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px';

        function setOv(prop, value) {
            if (value === '' || value === (base[prop] || '')) { delete rule.props[prop]; }
            else { rule.props[prop] = value; }
            sync();
        }
        function eff(prop) {
            return (rule.props[prop] != null) ? rule.props[prop] : (base[prop] || '');
        }

        SCHEMA.forEach(function (s) {
            var cell = document.createElement('div');
            cell.appendChild(mkLabel(s.label));
            var baseV = base[s.prop] || '';
            var val = eff(s.prop);

            if (s.type === 'color') {
                var isT = (val === 'transparent');
                var hasColor = /^#([0-9a-f]{6})$/i.test(val);
                var wrap = document.createElement('span');
                wrap.style.cssText = 'display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap';
                var color = document.createElement('input');
                color.type = 'color';
                color.value = hasColor ? val.toLowerCase() : '#000000';
                color.disabled = isT;
                var transChk = null;
                if (s.alpha) {
                    var tl = document.createElement('label');
                    tl.style.cssText = 'font-size:11px;color:#646970;display:inline-flex;gap:4px;align-items:center';
                    transChk = document.createElement('input');
                    transChk.type = 'checkbox';
                    transChk.checked = isT;
                    tl.appendChild(transChk);
                    tl.appendChild(document.createTextNode('transparent'));
                }
                color.addEventListener('input', function () {
                    if (transChk) transChk.checked = false;
                    color.disabled = false;
                    setOv(s.prop, color.value);
                });
                if (transChk) {
                    transChk.addEventListener('change', function () {
                        if (transChk.checked) { color.disabled = true; setOv(s.prop, 'transparent'); }
                        else { color.disabled = false; setOv(s.prop, color.value); }
                    });
                }
                wrap.appendChild(color);
                if (transChk) wrap.appendChild(transChk.parentNode);
                cell.appendChild(wrap);

            } else if (s.type === 'px') {
                var wrapPx = document.createElement('span');
                wrapPx.style.cssText = 'display:inline-flex;align-items:center;gap:6px';
                var num = document.createElement('input');
                num.type = 'number'; num.min = 0; num.max = s.max || 100; num.style.width = '80px';
                var nv = parseInt(val, 10);
                num.value = isNaN(nv) ? '' : nv;
                num.addEventListener('input', function () {
                    setOv(s.prop, num.value === '' ? '' : (parseInt(num.value, 10) + 'px'));
                });
                var u = document.createElement('span');
                u.style.cssText = 'font-size:11px;color:#888'; u.textContent = 'px';
                wrapPx.appendChild(num); wrapPx.appendChild(u);
                cell.appendChild(wrapPx);

            } else if (s.type === 'range01') {
                var baseNum = parseFloat(baseV || '1');
                var rng = document.createElement('input');
                rng.type = 'range'; rng.min = 0; rng.max = 100; rng.step = 1; rng.style.width = '100%';
                rng.value = Math.round((val === '' ? baseNum : parseFloat(val)) * 100);
                rng.addEventListener('input', function () {
                    var o = parseInt(rng.value, 10) / 100;
                    if (Math.abs(o - baseNum) < 0.005) { delete rule.props[s.prop]; sync(); }
                    else { rule.props[s.prop] = o.toString(); sync(); }
                });
                cell.appendChild(rng);

            } else if (s.type === 'select') {
                var selc = document.createElement('select'); selc.style.width = '100%';
                s.options.forEach(function (opt) {
                    var ov = (typeof opt === 'object') ? opt.v : opt;
                    var ol = (typeof opt === 'object') ? opt.l : (opt === '' ? '— défaut —' : opt);
                    var o = document.createElement('option');
                    o.value = ov; o.textContent = ol; if (ov === val) o.selected = true;
                    selc.appendChild(o);
                });
                selc.addEventListener('change', function () { setOv(s.prop, selc.value); });
                cell.appendChild(selc);

            } else if (s.type === 'font') {
                var ovFont = rule.props[s.prop] || '';
                var selF = document.createElement('select'); selF.style.width = '100%';
                var d0 = document.createElement('option'); d0.value = ''; d0.textContent = '— défaut —'; selF.appendChild(d0);
                FONTS.forEach(function (f) {
                    var o = document.createElement('option');
                    o.value = f; o.textContent = f.split(',')[0].replace(/['"]/g, '');
                    if (f === ovFont) o.selected = true;
                    selF.appendChild(o);
                });
                selF.addEventListener('change', function () { setOv(s.prop, selF.value); });
                cell.appendChild(selF);

            } else {
                var txt = document.createElement('input');
                txt.type = 'text'; txt.value = val; txt.placeholder = s.placeholder || baseV; txt.style.width = '100%';
                txt.addEventListener('input', function () { setOv(s.prop, txt.value.trim()); });
                cell.appendChild(txt);
            }
            grid.appendChild(cell);
        });

        var container = document.createElement('div');
        container.appendChild(grid);

        /* Propriétés libres. */
        var freeWrap = document.createElement('div');
        freeWrap.style.marginTop = '12px';
        freeWrap.appendChild(mkLabel('Autres déclarations (une par ligne : propriété: valeur;)'));
        var ta = document.createElement('textarea');
        ta.rows = 2; ta.style.cssText = 'width:100%;font-family:monospace;font-size:12px';
        ta.placeholder = 'letter-spacing: .04em;';
        ta.value = freeProps(rule.props);
        ta.addEventListener('input', function () {
            Object.keys(rule.props).forEach(function (k) { if (KNOWN.indexOf(k) === -1) delete rule.props[k]; });
            ta.value.split('\n').forEach(function (line) {
                var m = line.split(':');
                if (m.length >= 2) {
                    var p = m.shift().trim().toLowerCase();
                    var v = m.join(':').replace(/;/g, '').trim();
                    if (/^[a-z\-]+$/.test(p) && v) rule.props[p] = v;
                }
            });
            sync();
        });
        freeWrap.appendChild(ta);
        container.appendChild(freeWrap);
        return container;
    }

    /* Un item d'accordéon lié à `rule` — aucune reconstruction des autres. */
    function addItem(rule) {
        var item = document.createElement('div');
        item.className = 'ntb-cs-item';
        if (rule.locked) item.style.borderColor = '#2563eb';

        var head = document.createElement('div');
        head.className = 'ntb-cs-head';
        var caret = document.createElement('span');
        caret.textContent = '▾';
        caret.style.transition = 'transform .15s';
        var code = document.createElement('code');
        code.textContent = rule.selector;
        var lockBtn = document.createElement('button');
        lockBtn.type = 'button';
        lockBtn.textContent = rule.locked ? '🔒' : '🔓';
        lockBtn.title = rule.locked ? 'Toujours prioritaire — cliquer pour désactiver' : 'Cliquer pour toujours prioritaire';
        lockBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            rule.locked = !rule.locked;
            lockBtn.textContent = rule.locked ? '🔒' : '🔓';
            lockBtn.title = rule.locked ? 'Toujours prioritaire — cliquer pour désactiver' : 'Cliquer pour toujours prioritaire';
            item.style.borderColor = rule.locked ? '#2563eb' : '#dcdcde';
            sync();
        });
        var rm = document.createElement('button');
        rm.type = 'button'; rm.title = 'Supprimer';
        rm.textContent = '✕'; rm.style.color = '#b32d2e';

        head.appendChild(caret); head.appendChild(code); head.appendChild(lockBtn); head.appendChild(rm);

        var body = document.createElement('div');
        body.className = 'ntb-cs-body';
        body.appendChild(buildControls(rule, computeBase(rule.selector)));

        head.addEventListener('click', function (e) {
            if (e.target.closest('button')) return;
            var open = body.style.display !== 'none';
            body.style.display = open ? 'none' : '';
            caret.style.transform = open ? 'rotate(-90deg)' : '';
        });
        rm.addEventListener('click', function (e) {
            e.stopPropagation();
            var i = state.indexOf(rule);
            if (i > -1) state.splice(i, 1);
            item.remove();
            emptyEl.style.display = state.length ? 'none' : '';
            buildPreview();
            sync();
        });

        item.appendChild(head); item.appendChild(body);
        listEl.appendChild(item);
        return body;
    }

    function renderAll() {
        listEl.innerHTML = '';
        emptyEl.style.display = state.length ? 'none' : '';
        state.forEach(addItem);
        buildPreview();
        sync();
    }

    /* Élément DOM représentatif construit à partir d'un sélecteur. */
    function selToEl(selector, doc) {
        doc = doc || document;
        var group = (selector.split(',')[0] || '').trim();
        var parts = group.split(/\s+|>|\+|~/).filter(Boolean);
        var compound = (parts[parts.length - 1] || group).split(':')[0];
        var tagMatch = compound.match(/^[a-zA-Z][a-zA-Z0-9]*/);
        var tag = tagMatch ? tagMatch[0].toLowerCase() : 'div';
        var classes = (compound.match(/\.[A-Za-z0-9_\-]+/g) || []).map(function (c) { return c.slice(1); });
        var idMatch = compound.match(/#([A-Za-z0-9_\-]+)/);
        var el = doc.createElement(tag || 'div');
        classes.forEach(function (c) { el.classList.add(c); });
        if (idMatch) el.id = idMatch[1];
        el.classList.add('ntb-cs-prev-el');
        var label = classes[0] || (idMatch ? idMatch[1] : tag);
        if (tag === 'input') { el.type = 'text'; el.value = label; el.readOnly = true; }
        else { el.textContent = label; }
        return el;
    }

    /* Aperçu : un élément par règle, uniquement les sélecteurs ciblés. */
    function buildPreview() {
        prevBox.innerHTML = '';
        if (!state.length) {
            var hint = document.createElement('span');
            hint.style.cssText = 'color:#9aa0a6;font-size:13px';
            hint.textContent = 'Ajoutez un sélecteur pour voir son aperçu ici.';
            prevBox.appendChild(hint);
            return;
        }
        state.forEach(function (rule) {
            if (!rule.selector) return;
            var wrap = document.createElement('div');
            wrap.className = 'ntb-cs-prev-item';
            var sel = document.createElement('span');
            sel.className = 'ntb-cs-prev-sel';
            sel.textContent = rule.selector;
            wrap.appendChild(sel);
            wrap.appendChild(selToEl(rule.selector));
            prevBox.appendChild(wrap);
        });
    }

    function buildDecls(rule) {
        var decls = {};
        Object.keys(rule.props).forEach(function (k) { if (rule.props[k] !== '') decls[k] = rule.props[k]; });
        return decls;
    }

    function rulesCSS(scopePrefix) {
        var css = '';
        state.forEach(function (rule) {
            if (!rule.selector) return;
            var decls = buildDecls(rule);
            var keys = Object.keys(decls);
            if (!keys.length) return;
            var body = keys.map(function (k) { return '  ' + k + ': ' + decls[k] + ';'; }).join('\n');
            var sel = scopePrefix ? scopePrefix + ' ' + rule.selector.split(',').join(', ' + scopePrefix + ' ') : rule.selector;
            css += sel + ' {\n' + body + '\n}\n';
        });
        return css;
    }

    /* CSS généré + aperçu + JSON caché. */
    function sync() {
        genEl.value = rulesCSS('');
        prevEl.textContent = rulesCSS('#ntb-cs-preview');
        jsonEl.value = JSON.stringify(state.map(function (r) {
            return { selector: r.selector, props: buildDecls(r), locked: !!r.locked };
        }));
        saveDraft();
    }

    addBtn.addEventListener('click', function () {
        var sel = (selIn.value || '').trim();
        if (!sel) { selIn.focus(); return; }
        var rule = { selector: sel, props: {} };
        state.push(rule);
        selIn.value = '';
        emptyEl.style.display = 'none';
        var body = addItem(rule);
        buildPreview();
        sync();
        if (body) body.scrollIntoView({ block: 'nearest' });
    });
    selIn.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addBtn.click(); }
    });

    /* À l'enregistrement : sérialise puis efface le brouillon. */
    csForm.addEventListener('submit', function () {
        sync();
        clearDraft();
    });

    if (draftRestored) {
        var banner = document.createElement('div');
        banner.style.cssText = 'margin:0 0 14px;padding:8px 12px;display:flex;align-items:center;gap:12px;background:#fef3c7;border:1px solid #fcd34d;border-radius:6px;font-size:13px;';
        banner.innerHTML = '<span>Brouillon non enregistré restauré. Pensez à cliquer sur « Enregistrer ».</span>';
        var discard = document.createElement('button');
        discard.type = 'button';
        discard.className = 'nadm-btn nadm-btn--light';
        discard.textContent = 'Repartir des réglages enregistrés';
        discard.addEventListener('click', function () {
            clearDraft();
            state = savedState();
            banner.remove();
            renderAll();
        });
        banner.appendChild(discard);
        listEl.parentNode.insertBefore(banner, listEl);
    }

    renderAll();
    /* Charge le vrai CSS du tunnel dans un iframe isolé puis ré-affiche les
       contrôles avec les valeurs réelles de chaque élément. */
    initMeasure(renderAll);
})();
</script>
@endif
@endsection