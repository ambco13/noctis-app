@extends('layouts.admin')

@section('title', __('Réglages'))

@section('content')
<h1 class="ntb-page-title">{{ __('Réglages') }}</h1>

<form method="post" action="{{ route('admin.settings.update') }}" class="nadm-form">
    @csrf

    <h2 style="margin-top:0;">{{ __('Général') }}</h2>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
        <div><label>{{ __('Devise (ISO)') }}</label><input type="text" name="currency" value="{{ $values['currency'] }}"></div>
        <div><label>{{ __('Symbole') }}</label><input type="text" name="currency_symbol" value="{{ $values['currency_symbol'] }}"></div>
        <div><label>{{ __('Indicatif pays') }}</label><input type="text" name="default_country_code" value="{{ $values['default_country_code'] }}"></div>
        <div><label>{{ __('Format date') }}</label><input type="text" name="date_format" value="{{ $values['date_format'] }}"></div>
        <div><label>{{ __('Format heure') }}</label><input type="text" name="time_format" value="{{ $values['time_format'] }}"></div>
    </div>
    <p style="font-size:12px;color:#6b7280;">
        {{ __('Les clés API (Google, Stripe, PayPal, Twilio) se configurent dans le fichier .env, jamais ici.') }}
    </p>

    <h2>{{ __('Majorations') }}</h2>
    <label style="display:flex;gap:8px;align-items:center;">
        <input type="checkbox" name="surcharge_night_enabled" value="1" @checked($values['surcharge_night_enabled'])>
        {{ __('Tarif nuit') }}
    </label>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
        <div><label>{{ __('Début') }}</label><input type="time" name="surcharge_night_start" value="{{ $values['surcharge_night_start'] ?: '22:00' }}"></div>
        <div><label>{{ __('Fin') }}</label><input type="time" name="surcharge_night_end" value="{{ $values['surcharge_night_end'] ?: '06:00' }}"></div>
        <div><label>%</label><input type="number" step="0.1" min="0" name="surcharge_night_pct" value="{{ $values['surcharge_night_pct'] ?: 25 }}"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;">
        <label style="display:flex;gap:8px;align-items:center;">
            <input type="checkbox" name="surcharge_weekend_enabled" value="1" @checked($values['surcharge_weekend_enabled'])>
            {{ __('Week-end (%)') }}
            <input type="number" step="0.1" min="0" name="surcharge_weekend_pct" value="{{ $values['surcharge_weekend_pct'] ?: 20 }}" style="max-width:110px;">
        </label>
        <label style="display:flex;gap:8px;align-items:center;">
            <input type="checkbox" name="surcharge_holiday_enabled" value="1" @checked($values['surcharge_holiday_enabled'])>
            {{ __('Jours fériés (%)') }}
            <input type="number" step="0.1" min="0" name="surcharge_holiday_pct" value="{{ $values['surcharge_holiday_pct'] ?: 30 }}" style="max-width:110px;">
        </label>
    </div>

    <h2>{{ __('Notifications') }}</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="notif_email_customer" value="1" @checked($values['notif_email_customer'])> {{ __('Email client') }}</label>
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="notif_email_admin" value="1" @checked($values['notif_email_admin'])> {{ __('Email admin') }}</label>
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="notif_sms_customer" value="1" @checked($values['notif_sms_customer'])> {{ __('SMS client') }}</label>
        <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="notif_sms_admin" value="1" @checked($values['notif_sms_admin'])> {{ __('SMS admin') }}</label>
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

    <div style="margin-top:20px;">
        <button class="nadm-btn" type="submit">{{ __('Enregistrer les réglages') }}</button>
    </div>
</form>
@endsection