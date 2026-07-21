<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingTest extends TestCase
{
    // Le site vitrine ne lit que config/ (aucune requête DB) → pas de RefreshDatabase.

    public function test_accueil_nouveau_design(): void
    {
        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('Your chauffeur')             // hero
            ->assertSee('Effortless travel')          // section « promise »
            ->assertSee('Borders, handled.')          // panneau Europe
            ->assertSee('Trusted by travelers worldwide.') // témoignages
            ->assertSee('Estimate my ride');          // CTA
    }

    public function test_accueil_cta_pointe_vers_le_tunnel(): void
    {
        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee(route('booking.form', ['new' => 1]), false);
    }

    public function test_page_service_valide(): void
    {
        $this->get(route('marketing.service', ['slug' => 'airport-transfer']))
            ->assertOk()
            ->assertSee('Premium Paris Airport Transfer Service — Available 24/7');
    }

    public function test_service_retire_donne_404(): void
    {
        // « Special Event Limousine » a été retiré du nouveau design.
        $this->get(route('marketing.service', ['slug' => 'special-event-limousine']))->assertNotFound();
        $this->get(route('marketing.service', ['slug' => 'inexistant']))->assertNotFound();
    }

    public function test_pages_flotte_apropos_contact(): void
    {
        $this->get(route('marketing.fleet'))->assertOk()->assertSee('Chosen for comfort.');
        $this->get(route('marketing.about'))->assertOk()->assertSee('Refined mobility');
        $this->get(route('marketing.contact'))->assertOk()->assertSee("Let's talk.", false);
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
