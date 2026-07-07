<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://api-m.sandbox.paypal.com';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.paypal.client_id' => 'client_test',
            'services.paypal.secret' => 'secret_test',
            'services.paypal.sandbox' => true,
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
            'payment_method' => 'paypal',
            'payment_status' => 'pending',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_creation_ordre_lie_a_la_reservation(): void
    {
        $booking = $this->booking();

        Http::fake([
            self::API.'/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            self::API.'/v2/checkout/orders' => Http::response(['id' => 'ORDER123']),
        ]);

        $this->postJson('/api/v1/paypal/order', ['booking_id' => $booking->id])
            ->assertOk()
            ->assertJson(['success' => true, 'order_id' => 'ORDER123']);

        $this->assertSame('ORDER123', $booking->fresh()->transaction_id);
    }

    public function test_capture_confirme_la_reservation(): void
    {
        $booking = $this->booking(['transaction_id' => 'ORDER123']);

        Http::fake([
            self::API.'/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            self::API.'/v2/checkout/orders/ORDER123/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAP456']]]]],
            ]),
        ]);

        $this->postJson('/api/v1/paypal/capture', [
            'booking_id' => $booking->id,
            'order_id' => 'ORDER123',
        ])->assertOk()->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('CAP456', $booking->transaction_id);
    }

    public function test_capture_rejette_un_ordre_non_lie(): void
    {
        $booking = $this->booking(['transaction_id' => 'ORDER123']);

        $this->postJson('/api/v1/paypal/capture', [
            'booking_id' => $booking->id,
            'order_id' => 'ORDER_AUTRE',
        ])->assertStatus(422);

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_capture_non_completee_ne_confirme_pas(): void
    {
        $booking = $this->booking(['transaction_id' => 'ORDER123']);

        Http::fake([
            self::API.'/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            self::API.'/v2/checkout/orders/ORDER123/capture' => Http::response(['status' => 'PENDING']),
        ]);

        $this->postJson('/api/v1/paypal/capture', [
            'booking_id' => $booking->id,
            'order_id' => 'ORDER123',
        ])->assertStatus(422);

        $this->assertSame('pending', $booking->fresh()->status);
    }
}
