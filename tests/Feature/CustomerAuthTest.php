<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\MagicLink;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
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

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'amir@example.com',
            'first_name' => 'Amir',
            'last_name' => 'Cohen',
        ], $overrides));
    }

    /** Insère un magic link token valide et renvoie le token brut. */
    private function token(User $user, string $type = 'login', array $overrides = []): string
    {
        $raw = bin2hex(random_bytes(32));
        DB::table('magic_link_tokens')->insert(array_merge([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $raw),
            'type' => $type,
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return $raw;
    }

    public function test_inscription_cree_un_compte_pending_et_repond_generique(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'email' => 'nouveau@example.com',
            'first_name' => 'Nouveau',
        ])->assertCreated()
            ->assertJson(['message' => 'Un lien de connexion vous a été envoyé par email.']);

        $user = User::where('email', 'nouveau@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('pending', $user->account_status);
        $this->assertDatabaseHas('email_history', ['user_id' => $user->id, 'email' => 'nouveau@example.com']);
    }

    public function test_inscription_email_existant_reponse_identique_sans_doublon(): void
    {
        $this->user();

        $this->postJson('/api/v1/auth/register', ['email' => 'amir@example.com'])
            ->assertOk()
            ->assertJson(['message' => 'Un lien de connexion vous a été envoyé par email.']);

        $this->assertSame(1, User::where('email', 'amir@example.com')->count());
    }

    public function test_demande_magic_link_reponse_identique_compte_ou_non(): void
    {
        $this->user();

        $expected = 'Si un compte existe avec cette adresse, un lien vous a été envoyé.';

        $this->postJson('/api/v1/auth/magic-link/request', ['email' => 'amir@example.com'])
            ->assertOk()->assertJson(['message' => $expected]);
        $this->postJson('/api/v1/auth/magic-link/request', ['email' => 'inconnu@example.com'])
            ->assertOk()->assertJson(['message' => $expected]);

        // Un seul token créé (pour le compte existant).
        $this->assertSame(1, DB::table('magic_link_tokens')->count());
    }

    public function test_magic_link_connecte_et_active_le_compte(): void
    {
        $user = $this->user();
        $raw = $this->token($user);

        $this->get('/api/v1/auth/magic-link/verify?token='.$raw.'&redirect='.urlencode(url('/mon-compte')))
            ->assertRedirect(url('/mon-compte'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('active', $user->fresh()->account_status);
    }

    public function test_magic_link_deja_utilise_redirige_avec_erreur(): void
    {
        $user = $this->user();
        $raw = $this->token($user, 'login', ['used_at' => now()]);

        $response = $this->get('/api/v1/auth/magic-link/verify?token='.$raw);

        $this->assertGuest();
        $this->assertStringContainsString('auth_error=used', $response->headers->get('Location'));
    }

    public function test_magic_link_expire_redirige_avec_erreur(): void
    {
        $user = $this->user();
        $raw = $this->token($user, 'login', ['expires_at' => now()->subMinute()]);

        $response = $this->get('/api/v1/auth/magic-link/verify?token='.$raw);

        $this->assertGuest();
        $this->assertStringContainsString('auth_error=expired', $response->headers->get('Location'));
    }

    public function test_login_mot_de_passe(): void
    {
        $this->user(['password' => 'motdepasse123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'amir@example.com',
            'password' => 'motdepasse123',
        ])->assertOk();

        $this->assertAuthenticated();
    }

    public function test_login_mauvais_mot_de_passe_401(): void
    {
        $this->user(['password' => 'motdepasse123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'amir@example.com',
            'password' => 'mauvais',
        ])->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_rate_limiting_login(): void
    {
        $this->user(['password' => 'motdepasse123']);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'amir@example.com', 'password' => 'x']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'amir@example.com', 'password' => 'motdepasse123'])
            ->assertStatus(429);
    }

    public function test_changement_email_confirme_par_magic_link(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/customer/me/email', ['email' => 'nouvel@example.com'])
            ->assertOk();
        $this->assertSame('nouvel@example.com', $user->fresh()->pending_email);

        $raw = $this->token($user, MagicLink::TYPE_EMAIL_CHANGE);
        $this->actingAs($user)->get('/api/v1/auth/magic-link/verify?token='.$raw);

        $user->refresh();
        $this->assertSame('nouvel@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertDatabaseHas('email_history', ['user_id' => $user->id, 'email' => 'nouvel@example.com', 'is_current' => 1]);
    }

    public function test_changement_email_refuse_adresse_dun_autre_compte(): void
    {
        $user = $this->user();
        $this->user(['email' => 'autre@example.com']);

        $this->actingAs($user)->postJson('/api/v1/customer/me/email', ['email' => 'autre@example.com'])
            ->assertStatus(409);
    }

    public function test_profil_requiert_connexion(): void
    {
        $this->getJson('/api/v1/customer/me')->assertUnauthorized();
    }

    public function test_bookings_classees_et_rattachees_par_email(): void
    {
        $user = $this->user();

        $mk = fn (array $o) => Booking::create(array_merge([
            'booking_ref' => 'NTB-'.strtoupper(bin2hex(random_bytes(4))),
            'vehicle_name' => 'Berline',
            'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com',
            'phone' => '',
            'pickup_address' => 'A', 'dropoff_address' => 'B',
            'price' => 35, 'currency' => 'EUR',
            'payment_status' => 'paid', 'status' => 'confirmed',
        ], $o));

        $mk(['ride_date' => now()->addDays(3)->format('Y-m-d')]);                        // upcoming (email seul)
        $mk(['ride_date' => now()->subDays(3)->format('Y-m-d'), 'user_id' => $user->id]); // past
        $mk(['ride_date' => now()->addDay()->format('Y-m-d'), 'status' => 'cancelled']);  // cancelled
        $mk(['ride_date' => now()->addDay()->format('Y-m-d'), 'status' => 'pending']);    // exclue

        $res = $this->actingAs($user)->getJson('/api/v1/customer/bookings')->assertOk()->json();

        $this->assertCount(1, $res['upcoming']);
        $this->assertCount(1, $res['past']);
        $this->assertCount(1, $res['cancelled']);
    }

    public function test_suppression_compte_bloquee_par_reservation_future(): void
    {
        $user = $this->user();

        Booking::create([
            'booking_ref' => 'NTB-FUTUR001', 'user_id' => $user->id,
            'vehicle_name' => 'Berline', 'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com', 'phone' => '',
            'pickup_address' => 'A', 'dropoff_address' => 'B',
            'ride_date' => now()->addDays(2)->format('Y-m-d'),
            'price' => 35, 'currency' => 'EUR',
            'payment_status' => 'paid', 'status' => 'confirmed',
        ]);

        $this->actingAs($user)->postJson('/api/v1/customer/me/delete')
            ->assertStatus(409)
            ->assertJsonPath('code', 'future_bookings');

        $this->assertNotNull(User::find($user->id));
    }

    public function test_suppression_compte_anonymise_les_reservations(): void
    {
        $user = $this->user();

        $booking = Booking::create([
            'booking_ref' => 'NTB-PASSE001', 'user_id' => $user->id,
            'vehicle_name' => 'Berline', 'full_name' => 'Amir Cohen',
            'email' => 'amir@example.com', 'phone' => '+33612345678',
            'pickup_address' => 'A', 'dropoff_address' => 'B',
            'ride_date' => now()->subDays(10)->format('Y-m-d'),
            'price' => 35, 'currency' => 'EUR',
            'payment_status' => 'paid', 'status' => 'completed',
        ]);

        $this->actingAs($user)->postJson('/api/v1/customer/me/delete')->assertOk();

        $this->assertNull(User::find($user->id));
        $booking->refresh();
        $this->assertNull($booking->user_id);
        $this->assertSame('', $booking->email);
        $this->assertSame('Compte supprimé', $booking->full_name);
        $this->assertDatabaseMissing('email_history', ['email' => 'amir@example.com']);
    }

    public function test_suppression_compte_purge_la_fiche_contact(): void
    {
        $user = $this->user();
        Customer::create(['email' => 'amir@example.com', 'full_name' => 'Amir Cohen', 'phone' => '+33612345678']);

        $this->actingAs($user)->postJson('/api/v1/customer/me/delete')->assertOk();

        // La fiche contact (PII pure) est supprimée définitivement, pas anonymisée.
        $this->assertDatabaseMissing('customers', ['email' => 'amir@example.com']);
    }
}
