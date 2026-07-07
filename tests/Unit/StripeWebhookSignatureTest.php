<?php

namespace Tests\Unit;

use App\Services\Stripe;
use Tests\TestCase;

/**
 * Tests de Stripe::verifyWebhook() (portés du plugin d'origine).
 * Parsing de l'en-tête Stripe-Signature, HMAC en temps constant,
 * fenêtre anti-rejeu de 300 secondes.
 */
final class StripeWebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private const PAYLOAD = '{"id":"evt_1","type":"payment_intent.succeeded"}';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => self::SECRET]);
    }

    /**
     * Construit un en-tête Stripe-Signature valide pour un timestamp donné.
     */
    private function sign(int $timestamp, string $payload = self::PAYLOAD, string $secret = self::SECRET): string
    {
        $v1 = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return 't='.$timestamp.',v1='.$v1;
    }

    public function test_signature_valide(): void
    {
        $this->assertTrue(Stripe::verifyWebhook(self::PAYLOAD, $this->sign(time())));
    }

    public function test_signature_valide_avec_elements_supplementaires(): void
    {
        // Stripe envoie aussi v0 ; le parseur doit l'ignorer.
        $this->assertTrue(Stripe::verifyWebhook(self::PAYLOAD, $this->sign(time()).',v0=deadbeef'));
    }

    public function test_mauvais_secret_rejete(): void
    {
        $header = $this->sign(time(), self::PAYLOAD, 'whsec_wrong');
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, $header));
    }

    public function test_payload_altere_rejete(): void
    {
        $tampered = '{"id":"evt_1","type":"payment_intent.succeeded","amount":0}';
        $this->assertFalse(Stripe::verifyWebhook($tampered, $this->sign(time())));
    }

    public function test_rejeu_timestamp_trop_ancien_rejete(): void
    {
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, $this->sign(time() - 301)));
    }

    public function test_timestamp_futur_lointain_rejete(): void
    {
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, $this->sign(time() + 301)));
    }

    public function test_timestamp_recent_accepte(): void
    {
        $this->assertTrue(Stripe::verifyWebhook(self::PAYLOAD, $this->sign(time() - 250)));
    }

    public function test_en_tete_vide_rejete(): void
    {
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, ''));
    }

    public function test_en_tete_malforme_rejete(): void
    {
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, 'not-a-signature'));
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, 't='.time())); // v1 manquant.
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, 'v1=abc'));    // t manquant.
    }

    public function test_secret_non_configure_rejete(): void
    {
        config(['services.stripe.webhook_secret' => '']);
        $this->assertFalse(Stripe::verifyWebhook(self::PAYLOAD, $this->sign(time())));
    }
}
