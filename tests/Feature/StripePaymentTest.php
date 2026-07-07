<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\Stripe;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.stripe.key' => 'pk_test_x',
            'services.stripe.secret' => 'sk_test_x',
            'services.stripe.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
    }

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    private function booking(array $overrides = []): Booking
    {
        $vehicle = Vehicle::create([
            'name' => 'Berline',
            'base_fare' => 5, 'price_per_km' => 2, 'price_per_min' => 0.5,
            'min_price' => 10, 'capacity' => 4, 'luggage' => 2,
            'sort_order' => 1, 'is_active' => true,
        ]);

        return Booking::create(array_merge([
            'booking_ref' => 'NTB-TEST0001',
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->name,
            'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com',
            'phone' => '+33612345678',
            'pickup_address' => 'Paris',
            'dropoff_address' => 'Versailles',
            'ride_date' => now()->addDay()->format('Y-m-d'),
            'ride_time' => '14:30:00',
            'price' => 35,
            'currency' => 'EUR',
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'status' => 'pending',
            'transaction_id' => 'pi_123',
        ], $overrides));
    }

    private function signedWebhook(array $event): array
    {
        $payload = json_encode($event);
        $t = time();
        $v1 = hash_hmac('sha256', $t.'.'.$payload, self::WEBHOOK_SECRET);

        return [$payload, 't='.$t.',v1='.$v1];
    }

    public function test_confirm_stripe_verifie_le_paiement_cote_serveur(): void
    {
        $booking = $this->booking();

        Http::fake([
            Stripe::API_BASE.'/payment_intents/pi_123' => Http::response(['id' => 'pi_123', 'status' => 'succeeded']),
        ]);

        $this->postJson('/api/v1/confirm/stripe', [
            'booking_id' => $booking->id,
            'payment_intent_id' => 'pi_123',
        ])->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('summary.booking_ref', 'NTB-TEST0001');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
    }

    public function test_confirm_stripe_rejette_un_intent_non_lie_a_la_reservation(): void
    {
        $booking = $this->booking(['transaction_id' => 'pi_123']);

        $this->postJson('/api/v1/confirm/stripe', [
            'booking_id' => $booking->id,
            'payment_intent_id' => 'pi_AUTRE',
        ])->assertStatus(422);

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_confirm_stripe_rejette_un_paiement_non_abouti(): void
    {
        $booking = $this->booking();

        Http::fake([
            Stripe::API_BASE.'/payment_intents/pi_123' => Http::response(['id' => 'pi_123', 'status' => 'requires_payment_method']),
        ]);

        $this->postJson('/api/v1/confirm/stripe', [
            'booking_id' => $booking->id,
            'payment_intent_id' => 'pi_123',
        ])->assertStatus(422);
    }

    public function test_webhook_signature_invalide_renvoie_400(): void
    {
        $this->call('POST', '/api/v1/webhook/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 'bad',
            'CONTENT_TYPE' => 'application/json',
        ], '{}')->assertStatus(400);
    }

    public function test_webhook_payment_succeeded_confirme_la_reservation(): void
    {
        $booking = $this->booking();

        [$payload, $sig] = $this->signedWebhook([
            'id' => 'evt_1',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_123', 'metadata' => ['booking_ref' => 'NTB-TEST0001']]],
        ]);

        $this->call('POST', '/api/v1/webhook/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk()->assertJson(['received' => true]);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertDatabaseHas('stripe_events', ['event_id' => 'evt_1']);
    }

    public function test_webhook_est_idempotent(): void
    {
        $booking = $this->booking();

        [$payload, $sig] = $this->signedWebhook([
            'id' => 'evt_1',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_123', 'metadata' => ['booking_ref' => 'NTB-TEST0001']]],
        ]);

        $headers = ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/api/v1/webhook/stripe', [], [], [], $headers, $payload)->assertOk();
        $this->call('POST', '/api/v1/webhook/stripe', [], [], [], $headers, $payload)
            ->assertOk()->assertJson(['status' => 'already_processed']);
    }

    public function test_webhook_payment_failed_marque_la_reservation_echouee(): void
    {
        $booking = $this->booking();

        [$payload, $sig] = $this->signedWebhook([
            'id' => 'evt_2',
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => 'pi_123', 'metadata' => ['booking_ref' => 'NTB-TEST0001']]],
        ]);

        $this->call('POST', '/api/v1/webhook/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $booking->refresh();
        $this->assertSame('failed', $booking->status);
        $this->assertSame('failed', $booking->payment_status);
    }
}
