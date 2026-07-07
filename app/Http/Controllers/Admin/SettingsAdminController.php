<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsAdminController extends Controller
{
    /** Clés éditables et leur règle de validation. Les secrets API restent en .env. */
    private const FIELDS = [
        'currency' => ['string', 'max:8'],
        'currency_symbol' => ['string', 'max:8'],
        'date_format' => ['string', 'max:20'],
        'time_format' => ['string', 'max:20'],
        'default_country_code' => ['string', 'max:8'],

        'admin_notify_email' => ['nullable', 'email', 'max:191'],
        'admin_notify_phone' => ['nullable', 'string', 'max:40'],
        'notif_email_customer' => ['nullable'],
        'notif_email_admin' => ['nullable'],
        'notif_sms_customer' => ['nullable'],
        'notif_sms_admin' => ['nullable'],

        'tpl_email_customer_subject' => ['string', 'max:255'],
        'tpl_email_customer_body' => ['string', 'max:5000'],
        'tpl_email_admin_subject' => ['string', 'max:255'],
        'tpl_email_admin_body' => ['string', 'max:5000'],
        'tpl_sms_customer' => ['string', 'max:500'],
        'tpl_sms_admin' => ['string', 'max:500'],

        'surcharge_night_enabled' => ['nullable'],
        'surcharge_night_start' => ['nullable', 'date_format:H:i'],
        'surcharge_night_end' => ['nullable', 'date_format:H:i'],
        'surcharge_night_pct' => ['nullable', 'numeric', 'min:0', 'max:500'],
        'surcharge_weekend_enabled' => ['nullable'],
        'surcharge_weekend_pct' => ['nullable', 'numeric', 'min:0', 'max:500'],
        'surcharge_holiday_enabled' => ['nullable'],
        'surcharge_holiday_pct' => ['nullable', 'numeric', 'min:0', 'max:500'],
    ];

    private const CHECKBOXES = [
        'notif_email_customer', 'notif_email_admin', 'notif_sms_customer', 'notif_sms_admin',
        'surcharge_night_enabled', 'surcharge_weekend_enabled', 'surcharge_holiday_enabled',
    ];

    public function edit(): View
    {
        $values = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = Settings::get($key, '');
        }

        return view('admin.settings', ['values' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(self::FIELDS);

        foreach (array_keys(self::FIELDS) as $key) {
            if (in_array($key, self::CHECKBOXES, true)) {
                Settings::set($key, $request->has($key) ? '1' : '0');
            } elseif (array_key_exists($key, $validated)) {
                Settings::set($key, (string) ($validated[$key] ?? ''));
            }
        }

        return back()->with('ok', __('Réglages enregistrés.'));
    }
}
