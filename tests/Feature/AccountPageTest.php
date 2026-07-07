<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    public function test_visiteur_voit_le_formulaire_auth(): void
    {
        $this->get('/mon-compte')
            ->assertOk()
            ->assertSee('Mon compte')
            ->assertSee('ntb-email-check-form', false)
            ->assertDontSee('ntb-topbar', false);
    }

    public function test_formulaire_reset_affiche_avec_token(): void
    {
        $this->get('/mon-compte?reset=1&token=abc&email=a%40b.fr')
            ->assertOk()
            ->assertSee('Choisissez un nouveau mot de passe');
    }

    public function test_connecte_voit_ses_reservations(): void
    {
        $user = User::factory()->create(['email' => 'amir@example.com', 'first_name' => 'Amir', 'last_name' => 'Cohen']);

        Booking::create([
            'booking_ref' => 'NTB-DASH0001', 'user_id' => $user->id,
            'vehicle_name' => 'Berline', 'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com', 'phone' => '',
            'pickup_address' => 'Paris', 'dropoff_address' => 'Versailles',
            'ride_date' => now()->addDays(2)->format('Y-m-d'),
            'price' => 35, 'currency' => 'EUR',
            'payment_status' => 'paid', 'status' => 'confirmed',
        ]);

        $this->actingAs($user)->get('/mon-compte')
            ->assertOk()
            ->assertSee('NTB-DASH0001')
            ->assertSee('Mes réservations')
            ->assertSee('AC'); // Initiales avatar.
    }

    public function test_onglet_profil(): void
    {
        $user = User::factory()->create(['first_name' => 'Amir', 'phone' => '+33612345678']);

        $this->actingAs($user)->get('/mon-compte?tab=profile')
            ->assertOk()
            ->assertSee('Mon profil')
            ->assertSee('+33612345678');
    }
}
