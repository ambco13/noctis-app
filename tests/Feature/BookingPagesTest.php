<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    public function test_la_racine_porte_le_formulaire_de_reservation(): void
    {
        // La home EST l'étape 1 : son hero porte le vrai formulaire de réservation.
        $this->get('/')
            ->assertOk()
            ->assertSee('Your chauffeur')
            ->assertSee('Estimate my ride')
            ->assertSee('ntb-home-form', false);
    }

    public function test_reservation_redirige_vers_la_home(): void
    {
        // /reservation n'est plus une page distincte : elle redirige vers /.
        $this->get('/reservation')->assertRedirect('/');
    }

    public function test_etapes_sans_trajet_affiche_le_flux_vide(): void
    {
        $this->get('/ma-reservation')
            ->assertOk()
            ->assertSee('Démarrer une réservation');
    }

    public function test_soumission_etape_1_stocke_en_session_et_redirige(): void
    {
        $response = $this->post('/reservation', [
            'NTB2_pickup' => 'Paris, France',
            'NTB2_dropoff' => 'Versailles, France',
            'NTB2_date' => now()->addDay()->format('Y-m-d'),
            'NTB2_time' => '14:30',
        ]);

        $response->assertRedirect(route('booking.steps'));

        $this->get('/ma-reservation')
            ->assertOk()
            ->assertSee('Paris, France')
            ->assertSee('Choisissez votre expérience');
    }

    public function test_soumission_etape_1_rejette_une_date_passee(): void
    {
        $this->from('/reservation')->post('/reservation', [
            'NTB2_pickup' => 'Paris, France',
            'NTB2_dropoff' => 'Versailles, France',
            'NTB2_date' => now()->subDay()->format('Y-m-d'),
            'NTB2_time' => '14:30',
        ])->assertSessionHasErrors('NTB2_date');
    }
}
