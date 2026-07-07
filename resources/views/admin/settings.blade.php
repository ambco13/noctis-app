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
        <h3 style="margin-bottom:4px;">{{ $groupLabel }}</h3>
        @foreach ($group['fields'] as $key => $label)
            <label>{{ $label }}
                @if ($secrets[$key] !== '')
                    <span style="font-weight:400;color:#059669;font-size:12px;"> — {{ $secrets[$key] }}</span>
                @else
                    <span style="font-weight:400;color:#9ca3af;font-size:12px;"> — {{ __('non configurée') }}</span>
                @endif
            </label>
            <input type="text" name="secret_{{ $key }}" value="" placeholder="{{ __('Nouvelle valeur…') }}" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-bwignore>
        @endforeach
        @if ($group['hint'] !== '')
            <p style="font-size:12px;color:#6b7280;margin:6px 0 0;">{!! nl2br(e($group['hint'])) !!}
                @if (str_contains($group['hint'], 'webhook'))
                    <span class="nadm-code">{{ url('/api/v1/webhook/stripe') }}</span>
                @endif
            </p>
        @endif
        <div>
            <button type="button" class="nadm-test-btn" data-test-service="{{ $group['test'] }}">{{ __('Tester') }}</button>
            <span class="nadm-test-result" data-test-result="{{ $group['test'] }}"></span>
        </div>
    @endforeach

    <div style="margin-top:20px;"><button class="nadm-btn" type="submit">{{ __('Enregistrer les clés') }}</button></div>
</form>

<script>
    // Boutons « Tester la connexion » : teste avec les clés ENREGISTRÉES
    // (pensez à enregistrer avant de tester une nouvelle clé).
    document.querySelectorAll('[data-test-service]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var service = btn.getAttribute('data-test-service');
            var out = document.querySelector('[data-test-result="' + service + '"]');
            out.textContent = '…';
            out.style.color = '#6b7280';
            fetch(@json(route('admin.settings.test')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ service: service })
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
<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form">
    @csrf
    <input type="hidden" name="_tab" value="style">
    <h2 style="margin-top:0;">{{ __('Style du tunnel & de l\'espace client') }}</h2>
    <p style="font-size:12px;color:#6b7280;">
        {{ __('Les valeurs acceptent tout format CSS (hex, oklch, color-mix…). Les surfaces, lignes et textes sont dérivés automatiquement du fond si vous ne les surchargez pas.') }}
    </p>

    <h3>{{ __('Couleurs') }}</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><label>{{ __('Fond') }} <code>--ntb-bg</code></label>
            <input type="text" name="design[--ntb-bg]" value="{{ $design['--ntb-bg'] }}"></div>
        <div><label>{{ __('Accent') }} <code>--ntb-accent</code></label>
            <input type="text" name="design[--ntb-accent]" value="{{ $design['--ntb-accent'] }}"></div>
        <div><label>{{ __('Texte') }} <code>--ntb-text</code></label>
            <input type="text" name="design[--ntb-text]" value="{{ $design['--ntb-text'] }}"></div>
        <div><label>{{ __('Danger') }} <code>--ntb-danger</code></label>
            <input type="text" name="design[--ntb-danger]" value="{{ $design['--ntb-danger'] }}"></div>
        <div><label>{{ __('Texte sur accent') }} <code>--ntb-on-accent</code></label>
            <input type="text" name="design[--ntb-on-accent]" value="{{ $design['--ntb-on-accent'] }}"></div>
        <div><label>{{ __('Bordure des champs') }} <code>--ntb-field-line</code></label>
            <input type="text" name="design[--ntb-field-line]" value="{{ $design['--ntb-field-line'] }}"></div>
    </div>

    <h3>{{ __('Typographie') }}</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><label>{{ __('Serif (titres)') }}</label><input type="text" name="design[--ntb-serif]" value="{{ $design['--ntb-serif'] }}"></div>
        <div><label>{{ __('Sans (texte)') }}</label><input type="text" name="design[--ntb-sans]" value="{{ $design['--ntb-sans'] }}"></div>
    </div>

    <h3>{{ __('Géométrie') }}</h3>
    @php $cardRadius = (int) $design['--ntb-r']; @endphp
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        <div>
            <label>{{ __('Arrondi des cartes') }} — <output id="out-card-radius">{{ $cardRadius }}px</output></label>
            <input type="range" min="0" max="40" value="{{ $cardRadius }}" style="width:100%;"
                   oninput="document.getElementById('out-card-radius').value = this.value + 'px';
                            ['r','r-sm','r-lg'].forEach(s => document.getElementById('design-' + s).value = this.value + 'px');">
            <input type="hidden" id="design-r" name="design[--ntb-r]" value="{{ $design['--ntb-r'] }}">
            <input type="hidden" id="design-r-sm" name="design[--ntb-r-sm]" value="{{ $design['--ntb-r-sm'] }}">
            <input type="hidden" id="design-r-lg" name="design[--ntb-r-lg]" value="{{ $design['--ntb-r-lg'] }}">
        </div>
        <div>
            <label>{{ __('Arrondi des boutons') }} — <output id="out-btn-radius">{{ $design['ntb_btn_radius'] }}px</output></label>
            <input type="range" min="0" max="999" value="{{ $design['ntb_btn_radius'] }}" name="design[ntb_btn_radius]" style="width:100%;"
                   oninput="document.getElementById('out-btn-radius').value = this.value + 'px';">
            <p style="font-size:12px;color:#6b7280;margin:2px 0 0;">{{ __('0 = carré, 999 = pilule.') }}</p>
        </div>
    </div>

    <label style="margin-top:14px;">{{ __('Vitesse des animations') }}</label>
    <select name="design[--ntb-dur]" style="max-width:260px;">
        <option value="120ms" @selected($design['--ntb-dur'] === '120ms')>{{ __('Rapide') }}</option>
        <option value="220ms" @selected($design['--ntb-dur'] === '220ms')>{{ __('Standard (défaut)') }}</option>
        <option value="350ms" @selected($design['--ntb-dur'] === '350ms')>{{ __('Lent') }}</option>
    </select>

    <h3>{{ __('Effet verre') }}</h3>
    @php $blurPx = (int) preg_replace('/\D/', '', $design['--ntb-glass-blur']); @endphp
    <label style="display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="design[ntb_glass_enabled]" value="{{ $design['ntb_glass_enabled'] !== '0' ? '1' : '0' }}">
        <input type="checkbox" class="nadm-sw" onchange="this.previousElementSibling.value = this.checked ? '1' : '0'" @checked($design['ntb_glass_enabled'] !== '0')>
        {{ __('Activer l\'effet verre (glassmorphisme)') }}
    </label>
    <p style="font-size:12px;color:#6b7280;margin:4px 0 12px;">
        {{ __('Fond semi-transparent + flou du panneau de réservation. Réduire l\'opacité renforce l\'effet verre.') }}
    </p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        <div>
            <label>{{ __('Opacité du fond') }} — <output id="out-glass-op">{{ $design['ntb_glass_opacity'] }}%</output></label>
            <input type="range" min="0" max="100" value="{{ $design['ntb_glass_opacity'] }}" name="design[ntb_glass_opacity]" style="width:100%;"
                   oninput="document.getElementById('out-glass-op').value = this.value + '%';">
        </div>
        <div>
            <label>{{ __('Intensité du flou') }} — <output id="out-glass-blur">{{ $blurPx }}px</output></label>
            <input type="range" min="0" max="20" value="{{ $blurPx }}" style="width:100%;"
                   oninput="document.getElementById('out-glass-blur').value = this.value + 'px';
                            document.getElementById('design-glass-blur').value = 'blur(' + this.value + 'px)';">
            <input type="hidden" id="design-glass-blur" name="design[--ntb-glass-blur]" value="{{ $design['--ntb-glass-blur'] }}">
        </div>
    </div>

    <h3>{{ __('CSS personnalisé') }}</h3>
    <textarea name="custom_css" rows="8" style="font-family:ui-monospace,monospace;font-size:12px;" placeholder=".ntb-scope .ntb-btn { … }">{{ $customCss }}</textarea>

    <div style="margin-top:20px;display:flex;gap:10px;">
        <button class="nadm-btn" type="submit">{{ __('Enregistrer le style') }}</button>
        <button class="nadm-btn nadm-btn--danger" type="submit" name="_action" value="reset"
                onclick="return confirm(@json(__('Réinitialiser tout le style (tokens + CSS personnalisé) aux valeurs par défaut ?')));">
            {{ __('Réinitialiser') }}
        </button>
        <a class="nadm-btn nadm-btn--light" href="{{ route('booking.form') }}" target="_blank">{{ __('Voir le site') }}</a>
    </div>
</form>
@endif
@endsection