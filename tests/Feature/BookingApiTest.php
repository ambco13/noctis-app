<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\GoogleMaps;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google_maps.key' => 'test-key']);

        // Réponse Routes API simulée : 10 km, 20 min.
        Http::fake([
            GoogleMaps::ROUTES_ENDPOINT => Http::response([
                'routes' => [[
                    'distanceMeters' => 10000,
                    'duration' => '1200s',
                    'polyline' => ['encodedPolyline' => 'abc123'],
                ]],
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create([
            'name' => 'Berline',
            'base_fare' => 5, 'price_per_km' => 2, 'price_per_min' => 0.5,
            'min_price' => 10, 'capacity' => 4, 'luggage' => 2,
            'sort_order' => 1, 'is_active' => true,
        ]);
    }

    public function test_quotes_renvoie_le_contrat_attendu_par_le_front(): void
    {
        $this->vehicle();

        $this->postJson('/api/v1/quotes', [
            'pickup_address' => 'Paris, France',
            'dropoff_address' => 'Versailles, France',
        ])->assertOk()->assertJson([
            'success' => true,
            'distance_km' => 10,
            'duration_min' => 20,
            'polyline' => 'abc123',
        ])->assertJsonPath('vehicles.0.name', 'Berline')
            // 5 + 2×10 + 0,5×20 = 35.
            ->assertJsonPath('vehicles.0.price', 35);
    }

    public function test_booking_cree_une_reservation_pending_au_prix_serveur(): void
    {
        $vehicle = $this->vehicle();

        $response = $this->postJson('/api/v1/booking', [
            'vehicle_id' => $vehicle->id,
            'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com',
            'phone' => '+33612345678',
            'pickup_address' => 'Paris, France',
            'dropoff_address' => 'Versailles, France',
            'ride_date' => now()->addDay()->format('Y-m-d'),
            'ride_time' => '14:30',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'amount' => 35, 'currency' => 'EUR']);

        $booking = Booking::first();
        $this->assertSame('pending', $booking->status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame(35.0, $booking->price);
        $this->assertStringStartsWith('NTB-', $booking->booking_ref);
        $this->assertNotNull($booking->customer_id);
    }

    public function test_booking_refuse_un_vehicule_inconnu(): void
    {
        $this->postJson('/api/v1/booking', [
            'vehicle_id' => 999,
            'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com',
            'phone' => '+33612345678',
            'pickup_address' => 'Paris, France',
            'dropoff_address' => 'Versailles, France',
            'ride_date' => now()->addDay()->format('Y-m-d'),
            'ride_time' => '14:30',
        ])->assertStatus(422);
    }

    public function test_autocomplete_sans_cle_renvoie_une_liste_vide(): void
    {
        config(['services.google_maps.key' => '']);

        $this->getJson('/api/v1/autocomplete?q=Paris')
            ->assertOk()
            ->assertExactJson(['predictions' => []]);
    }
}
