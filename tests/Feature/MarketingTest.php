<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTest extends TestCase
{
    // L'accueil embarque le hero de réservation, qui lit Settings/Secrets en base.
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    public function test_accueil_affiche_hero_reel_et_sections(): void
    {
        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('Our Competences')              // section marketing
            ->assertSee('AIRPORT TRANSFER')             // teaser marketing
            ->assertSee('Estimer ma course')            // hero réel (FR)
            ->assertSee('data-ntb-autocomplete', false); // autocomplétion câblée
    }

    public function test_hero_accueil_poste_dans_le_tunnel(): void
    {
        // Le formulaire du hero pointe bien vers l'étape 1 du tunnel existant.
        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee(route('booking.step1'), false);
    }

    public function test_page_service_valide(): void
    {
        $this->get(route('marketing.service', ['slug' => 'airport-transfer']))
            ->assertOk()
            ->assertSee('Premium Paris Airport Transfer Service — Available 24/7');
    }

    public function test_slug_service_inconnu_donne_404(): void
    {
        $this->get(route('marketing.service', ['slug' => 'inexistant']))
            ->assertNotFound();
    }

    public function test_pages_flotte_apropos_contact(): void
    {
        $this->get(route('marketing.fleet'))->assertOk()->assertSee('Our Fleet');
        $this->get(route('marketing.about'))->assertOk()->assertSee('About Us');
        $this->get(route('marketing.contact'))->assertOk()->assertSee('Contact Us');
    }

    public function test_soumission_contact_valide_et_flash(): void
    {
        $this->post(route('marketing.contact.submit'), [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'message' => 'Bonjour, une demande de devis.',
        ])
            ->assertRedirect(route('marketing.contact'))
            ->assertSessionHas('status');
    }

    public function test_soumission_contact_invalide_est_rejetee(): void
    {
        $this->post(route('marketing.contact.submit'), [
            'name' => '',
            'email' => 'pas-un-email',
            'message' => '',
        ])->assertSessionHasErrors(['name', 'email', 'message']);
    }
}
