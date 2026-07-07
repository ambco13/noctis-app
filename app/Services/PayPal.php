<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Support\Secrets;
use Illuminate\Support\Facades\Http;

/**
 * Passerelle de paiement PayPal (Orders API v2).
 * Flux : create order (serveur) → approbation client (SDK JS) → capture (serveur).
 */
class PayPal
{
    public static function apiBase(): string
    {
        // Le mode test global (réglage admin) pilote sandbox vs live.
        return Secrets::isTestMode()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Jeton d'accès OAuth2 (client_credentials).
     *
     * @throws BookingException
     */
    private static function accessToken(): string
    {
        $client = Secrets::paypalClientId();
        $secret = Secrets::paypalSecret();

        if ($client === '' || $secret === '') {
            throw new BookingException(__('Identifiants PayPal manquants.'));
        }

        $response = Http::timeout(20)
            ->withBasicAuth($client, $secret)
            ->asForm()
            ->post(self::apiBase().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        $token = (string) $response->json('access_token', '');
        if ($token === '') {
            throw new BookingException(__('Authentification PayPal échouée.'));
        }

        return $token;
    }

    /**
     * Crée un ordre PayPal.
     *
     * @return array{id: string}
     *
     * @throws BookingException
     */
    public static function createOrder(float $amount, string $currency, string $bookingRef): array
    {
        $response = Http::timeout(20)
            ->withToken(self::accessToken())
            ->post(self::apiBase().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $bookingRef,
                    'description' => 'NOCTIS '.$bookingRef,
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
            ]);

        $id = (string) $response->json('id', '');
        if ($id === '') {
            throw new BookingException(__("Création de l'ordre PayPal échouée."));
        }

        return ['id' => $id];
    }

    /**
     * Capture un ordre PayPal approuvé.
     *
     * @return array{paid: bool, capture_id: string}
     *
     * @throws BookingException
     */
    public static function captureOrder(string $orderId): array
    {
        $response = Http::timeout(25)
            ->withToken(self::accessToken())
            ->withBody('{}', 'application/json')
            ->post(self::apiBase().'/v2/checkout/orders/'.rawurlencode($orderId).'/capture');

        $data = (array) $response->json();

        return [
            'paid' => ($data['status'] ?? '') === 'COMPLETED',
            'capture_id' => (string) ($data['purchase_units'][0]['payments']['captures'][0]['id'] ?? ''),
        ];
    }
}
