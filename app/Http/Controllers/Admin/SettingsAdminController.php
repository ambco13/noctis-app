<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Design;
use App\Support\Secrets;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsAdminController extends Controller
{
    /** Clés éditables et leur règle de validation. */
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

    /** Cases a cocher par onglet : absentes du POST = decochees, mais
     * uniquement pour l'onglet soumis (chaque onglet a son formulaire). */
    private const TAB_CHECKBOXES = [
        'tarifs' => ['surcharge_night_enabled', 'surcharge_weekend_enabled', 'surcharge_holiday_enabled'],
        'notifications' => ['notif_email_customer', 'notif_email_admin', 'notif_sms_customer', 'notif_sms_admin'],
    ];

    public function edit(Request $request): View
    {
        $values = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = Settings::get($key, '');
        }

        $secrets = [];
        foreach (array_keys(Secrets::KEYS) as $key) {
            $secrets[$key] = Secrets::masked($key);
        }

        return view('admin.settings', [
            'values' => $values,
            'secrets' => $secrets,
            'design' => Design::tokens(),
            'designDefaults' => Design::defaults(),
            'customCss' => Design::customCss(),
            'tab' => (string) $request->query('tab', 'general'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = (string) $request->input('_tab', 'general');

        // Onglet Style : tokens design + CSS personnalisé (ou réinitialisation).
        if ($tab === 'style') {
            if ($request->input('_action') === 'reset') {
                Design::reset();

                return redirect()->route('admin.settings', ['tab' => 'style'])
                    ->with('ok', __('Style réinitialisé aux valeurs par défaut.'));
            }

            Design::save((array) $request->input('design', []));
            Design::saveCustomCss((string) $request->input('custom_css', ''));

            return redirect()->route('admin.settings', ['tab' => 'style'])
                ->with('ok', __('Style enregistré.'));
        }

        // Onglet API : clés chiffrées ; champ vide = inchangé, « - » = effacer.
        if ($tab === 'api') {
            foreach (array_keys(Secrets::KEYS) as $key) {
                $input = trim((string) $request->input('secret_'.$key, ''));
                if ($input === '') {
                    continue;
                }
                Secrets::set($key, $input === '-' ? '' : $input);
            }

            return redirect()->route('admin.settings', ['tab' => 'api'])
                ->with('ok', __('Clés API enregistrées (chiffrées).'));
        }

        // Autres onglets : réglages simples.
        $validated = $request->validate(self::FIELDS);
        $checkboxes = self::TAB_CHECKBOXES[$tab] ?? [];
        $allCheckboxes = array_merge(...array_values(self::TAB_CHECKBOXES));

        foreach ($checkboxes as $key) {
            Settings::set($key, $request->has($key) ? '1' : '0');
        }
        foreach (array_keys(self::FIELDS) as $key) {
            if (! in_array($key, $allCheckboxes, true) && array_key_exists($key, $validated)) {
                Settings::set($key, (string) ($validated[$key] ?? ''));
            }
        }

        return redirect()->route('admin.settings', ['tab' => $tab])
            ->with('ok', __('Réglages enregistrés.'));
    }
}
