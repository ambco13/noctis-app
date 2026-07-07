<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_admin' => true])->save();

        return $u;
    }

    private function booking(): Booking
    {
        return Booking::create([
            'booking_ref' => 'NTB-ADMIN001',
            'vehicle_name' => 'Berline', 'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com', 'phone' => '',
            'pickup_address' => 'Paris', 'dropoff_address' => 'Versailles',
            'ride_date' => now()->addDay()->format('Y-m-d'),
            'price' => 35, 'currency' => 'EUR',
            'payment_status' => 'paid', 'status' => 'confirmed',
        ]);
    }

    public function test_visiteur_redirige_vers_mon_compte(): void
    {
        $this->get('/admin')->assertRedirect(route('account'));
    }

    public function test_client_non_admin_recoit_403(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_dashboard_affiche_stats_et_recentes(): void
    {
        $this->booking();

        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('NTB-ADMIN001')
            ->assertSee('35,00');
    }

    public function test_liste_et_filtre_reservations(): void
    {
        $this->booking();

        $this->actingAs($this->admin())->get('/admin/reservations?status=confirmed')
            ->assertOk()->assertSee('NTB-ADMIN001');
        $this->actingAs($this->admin())->get('/admin/reservations?status=cancelled')
            ->assertOk()->assertDontSee('NTB-ADMIN001');
        $this->actingAs($this->admin())->get('/admin/reservations?s=ADMIN001')
            ->assertOk()->assertSee('NTB-ADMIN001');
    }

    public function test_changement_de_statut(): void
    {
        $b = $this->booking();

        $this->actingAs($this->admin())
            ->post("/admin/reservations/{$b->id}/statut", ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame('completed', $b->fresh()->status);
    }

    public function test_crud_vehicule(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/vehicules', [
            'name' => 'Luxe', 'base_fare' => 10, 'price_per_km' => 3,
            'price_per_min' => 0.8, 'min_price' => 40, 'capacity' => 3,
            'luggage' => 2, 'sort_order' => 5, 'is_active' => 1,
        ])->assertRedirect(route('admin.vehicles'));

        $v = Vehicle::where('name', 'Luxe')->first();
        $this->assertNotNull($v);
        $this->assertTrue($v->is_active);

        $this->actingAs($admin)->put("/admin/vehicules/{$v->id}", [
            'name' => 'Luxe+', 'base_fare' => 12, 'price_per_km' => 3,
            'price_per_min' => 0.8, 'min_price' => 40, 'capacity' => 3, 'luggage' => 2,
        ])->assertRedirect();

        $v->refresh();
        $this->assertSame('Luxe+', $v->name);
        $this->assertFalse($v->is_active); // Checkbox absente = décochée.

        $this->actingAs($admin)->delete("/admin/vehicules/{$v->id}")->assertRedirect();
        $this->assertNull(Vehicle::find($v->id));
    }

    public function test_reglages_enregistres(): void
    {
        $this->actingAs($this->admin())->post('/admin/reglages', [
            '_tab' => 'tarifs',
            'currency' => 'EUR', 'currency_symbol' => '€',
            'date_format' => 'd/m/Y', 'time_format' => 'H:i', 'default_country_code' => '+33',
            'surcharge_weekend_enabled' => '1', 'surcharge_weekend_pct' => '22.5',
            'tpl_email_customer_subject' => 'Sujet {numero_reservation}',
            'tpl_email_customer_body' => 'Corps', 'tpl_email_admin_subject' => 'S',
            'tpl_email_admin_body' => 'C', 'tpl_sms_customer' => 's', 'tpl_sms_admin' => 's',
        ])->assertRedirect();

        Settings::flush();
        $this->assertSame('1', Settings::get('surcharge_weekend_enabled'));
        $this->assertSame('22.5', Settings::get('surcharge_weekend_pct'));
        $this->assertNull(Settings::get('notif_email_customer')); // Checkbox d'un autre onglet : intacte.
    }
}
