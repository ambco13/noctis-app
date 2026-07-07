<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleMaps;
use App\Services\PayPal;
use App\Services\Stripe;
use App\Support\Design;
use App\Support\Secrets;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        'aside_detach_body' => ['nullable'],

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
        'general' => ['aside_detach_body'],
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

        // Onglet API : mode test + clés chiffrées ; champ vide = inchangé, « - » = effacer.
        if ($tab === 'api') {
            Settings::set('test_mode', $request->has('test_mode') ? '1' : '0');

            foreach (array_keys(Secrets::KEYS) as $key) {
                $input = trim((string) $request->input('apikey_'.$key, ''));
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

    /**
     * POST /admin/reglages/test — teste la connexion d'un service avec les
     * clés effectives (admin ou .env, selon le mode test/live).
     */
    public function testService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service' => ['required', 'in:google,stripe,paypal,twilio'],
        ]);

        try {
            $message = match ($validated['service']) {
                'google' => $this->testGoogle(),
                'stripe' => $this->testStripe(),
                'paypal' => $this->testPaypal(),
                'twilio' => $this->testTwilio(),
            };

            return response()->json(['ok' => true, 'message' => $message]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    private function testGoogle(): string
    {
        $route = GoogleMaps::computeRoute('Paris, France', 'Versailles, France');

        return __('Itinéraire test OK (:km km).', ['km' => $route['distance_km']]);
    }

    private function testStripe(): string
    {
        $secret = Secrets::stripeSecret();
        if ($secret === '') {
            throw new \RuntimeException(__('Clé secrète Stripe manquante.'));
        }

        $response = Http::timeout(15)->withToken($secret)
            ->get(Stripe::API_BASE.'/balance');

        if ($response->failed()) {
            throw new \RuntimeException($response->json('error.message') ?? __('Connexion Stripe échouée.'));
        }

        return __('Clé Stripe valide (:mode).', ['mode' => Secrets::isTestMode() ? 'test' : 'live']);
    }

    private function testPaypal(): string
    {
        $client = Secrets::paypalClientId();
        $secret = Secrets::paypalSecret();
        if ($client === '' || $secret === '') {
            throw new \RuntimeException(__('Identifiants PayPal manquants.'));
        }

        $response = Http::timeout(15)
            ->withBasicAuth($client, $secret)->asForm()
            ->post(PayPal::apiBase().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ((string) $response->json('access_token', '') === '') {
            throw new \RuntimeException($response->json('error_description') ?? __('Authentification PayPal échouée.'));
        }

        return __('Identifiants PayPal valides (:mode).', ['mode' => Secrets::isTestMode() ? 'sandbox' : 'live']);
    }

    private function testTwilio(): string
    {
        $sid = Secrets::get('twilio_sid');
        $token = Secrets::get('twilio_token');
        if ($sid === '' || $token === '') {
            throw new \RuntimeException(__('Identifiants Twilio incomplets.'));
        }

        $response = Http::timeout(15)
            ->withBasicAuth($sid, $token)
            ->get('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($sid).'.json');

        if ($response->failed()) {
            throw new \RuntimeException(__('Connexion Twilio échouée (vérifiez SID/Token).'));
        }

        return __('Compte Twilio accessible.');
    }
}
