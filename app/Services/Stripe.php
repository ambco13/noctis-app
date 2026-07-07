<?php

namespace App\Services;

use App\Exceptions\BookingException;
use Illuminate\Support\Facades\Http;

/**
 * Passerelle de paiement Stripe (API REST directe, sans SDK).
 * Aucune donnée de carte ne transite par le serveur : tout passe par le
 * Payment Element côté navigateur. On manipule ici uniquement des
 * PaymentIntents côté serveur.
 */
class Stripe
{
    public const API_BASE = 'https://api.stripe.com/v1';

    /**
     * Requête authentifiée vers l'API Stripe.
     *
     * @throws BookingException
     */
    private static function request(string $method, string $path, array $body = []): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            throw new BookingException(__('Clé secrète Stripe manquante.'));
        }

        $pending = Http::timeout(20)->withToken($secret)->asForm();

        $response = $method === 'GET'
            ? $pending->get(self::API_BASE.$path)
            : $pending->send($method, self::API_BASE.$path, ['form_params' => $body]);

        $data = (array) $response->json();

        if ($response->failed()) {
            throw new BookingException($data['error']['message'] ?? __('Erreur Stripe.'));
        }

        return $data;
    }

    /**
     * Crée un PaymentIntent pour une réservation.
     *
     * @return array{client_secret: string, id: string, status: string}
     *
     * @throws BookingException
     */
    public static function createPaymentIntent(float $amount, string $currency, string $bookingRef): array
    {
        $res = self::request('POST', '/payment_intents', [
            'amount' => (int) round($amount * 100),
            'currency' => strtolower($currency),
            'automatic_payment_methods' => ['enabled' => 'true'],
            'metadata' => ['booking_ref' => $bookingRef],
            'description' => sprintf('NOCTIS %s', $bookingRef),
        ]);

        return [
            'client_secret' => $res['client_secret'] ?? '',
            'id' => $res['id'],
            'status' => $res['status'] ?? 'requires_payment_method',
        ];
    }

    /**
     * @throws BookingException
     */
    public static function retrievePaymentIntent(string $intentId): array
    {
        return self::request('GET', '/payment_intents/'.rawurlencode($intentId));
    }

    /**
     * Vérifie côté serveur qu'un paiement est bien abouti.
     */
    public static function isPaid(string $intentId): bool
    {
        try {
            $pi = self::retrievePaymentIntent($intentId);
        } catch (BookingException) {
            return false;
        }

        return ($pi['status'] ?? '') === 'succeeded';
    }

    /**
     * Rembourse un PaymentIntent (remboursement total).
     *
     * @throws BookingException
     */
    public static function refund(string $paymentIntentId): void
    {
        self::request('POST', '/refunds', ['payment_intent' => $paymentIntentId]);
    }

    /**
     * Vérifie la signature d'un webhook Stripe (HMAC + fenêtre anti-rejeu 300 s).
     */
    public static function verifyWebhook(string $payload, string $sigHeader): bool
    {
        $secret = trim((string) config('services.stripe.webhook_secret'));
        if ($secret === '' || $sigHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sigHeader) as $kv) {
            $pair = explode('=', $kv, 2);
            if (count($pair) === 2) {
                $parts[trim($pair[0])] = trim($pair[1]);
            }
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        // Stripe recommande de rejeter tout webhook de plus de 300 secondes
        // pour neutraliser les attaques par rejeu d'événements signés.
        if (abs(time() - (int) $parts['t']) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'].'.'.$payload, $secret);

        return hash_equals($expected, $parts['v1']);
    }
}
