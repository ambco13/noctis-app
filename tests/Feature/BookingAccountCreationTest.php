<?php

namespace Tests\Feature;

use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CustomerService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Création silencieuse d'un compte client lors d'une réservation invité payée,
 * et préservation du profil des comptes déjà configurés.
 */
class BookingAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    private function booking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_ref' => 'NTB-'.strtoupper(bin2hex(random_bytes(4))),
            'vehicle_name' => 'Berline',
            'full_name' => 'Amir Cohen',
            'email' => 'client@example.com',
            'phone' => '+33612345678',
            'pickup_address' => 'A', 'dropoff_address' => 'B',
            'ride_date' => now()->addDays(2)->format('Y-m-d'),
            'price' => 35, 'currency' => 'EUR',
            'payment_status' => 'paid', 'status' => 'confirmed',
        ], $overrides));
    }

    public function test_reservation_invite_cree_un_compte_pending_rattache(): void
    {
        $booking = $this->booking();

        $result = CustomerService::ensureAccountForBooking($booking);

        $this->assertTrue($result['created']);

        $user = User::where('email', 'client@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('pending', $user->account_status);
        $this->assertNull($user->password);
        $this->assertSame($user->id, $booking->fresh()->user_id);
        $this->assertDatabaseHas('email_history', ['user_id' => $user->id, 'email' => 'client@example.com']);
    }

    public function test_compte_actif_deja_configure_nom_non_ecrase(): void
    {
        $user = User::factory()->create([
            'name' => 'Prénom Configuré',
            'first_name' => 'Prénom',
            'last_name' => 'Configuré',
            'email' => 'client@example.com',
            'phone' => '+33600000000',
        ]);
        $user->forceFill(['account_status' => 'active'])->save();

        $booking = $this->booking(['full_name' => 'Autre Nom', 'phone' => '+33699999999']);

        $result = CustomerService::ensureAccountForBooking($booking);

        $this->assertFalse($result['created']);
        $user->refresh();
        $this->assertSame('Prénom Configuré', $user->name);
        $this->assertSame('+33600000000', $user->phone);
        $this->assertSame($user->id, $booking->fresh()->user_id);
        $this->assertSame(1, User::where('email', 'client@example.com')->count());
    }

    public function test_compte_pending_non_configure_contact_mis_a_jour(): void
    {
        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'client@example.com',
            'phone' => '',
        ]);
        $user->forceFill(['account_status' => 'pending'])->save();

        $booking = $this->booking(['full_name' => 'Nouveau Nom', 'phone' => '+33611111111']);

        $result = CustomerService::ensureAccountForBooking($booking);

        $this->assertFalse($result['created']);
        $user->refresh();
        $this->assertSame('Nouveau Nom', $user->name);
        $this->assertSame('+33611111111', $user->phone);
    }

    public function test_confirmpaid_signale_la_creation_dans_l_event(): void
    {
        Event::fake([BookingConfirmed::class]);

        $booking = $this->booking();
        $booking->update(['status' => 'pending', 'payment_status' => 'pending', 'user_id' => null]);

        BookingService::confirmPaid($booking->id, 'pi_test');

        Event::assertDispatched(BookingConfirmed::class, fn (BookingConfirmed $e) => $e->accountCreated === true
            && $e->booking->id === $booking->id);

        $this->assertNotNull(User::where('email', 'client@example.com')->first());
    }
}
