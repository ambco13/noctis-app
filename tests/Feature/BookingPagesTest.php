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

    public function test_la_racine_redirige_vers_le_formulaire(): void
    {
        $this->get('/')->assertRedirect('/reservation');
    }

    public function test_le_formulaire_etape_1_saffiche(): void
    {
        $this->get('/reservation')
            ->assertOk()
            ->assertSee('Estimer ma course')
            ->assertSee('ntb-home-form', false);
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
