<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Design;
use App\Support\Secrets;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecretsAndDesignTest extends TestCase
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

    /* ── Secrets ─────────────────────────────────────────────── */

    public function test_secret_chiffre_en_base_et_dechiffre_a_la_lecture(): void
    {
        Secrets::set('stripe_secret', 'sk_test_abc123');
        Settings::flush();

        // Jamais stocké en clair.
        $stored = DB::table('settings')->where('key', 'secret_stripe_secret')->value('value');
        $this->assertStringNotContainsString('sk_test_abc123', $stored);

        $this->assertSame('sk_test_abc123', Secrets::get('stripe_secret'));
    }

    public function test_priorite_admin_puis_env(): void
    {
        config(['services.google_maps.key' => 'cle-env']);
        $this->assertSame('cle-env', Secrets::get('google_maps_key'));

        Secrets::set('google_maps_key', 'cle-admin');
        Settings::flush();
        $this->assertSame('cle-admin', Secrets::get('google_maps_key'));

        // Effacement de l'override admin → retour au .env.
        Secrets::set('google_maps_key', '');
        Settings::flush();
        $this->assertSame('cle-env', Secrets::get('google_maps_key'));
    }

    public function test_onglet_api_enregistre_sans_ecraser_les_champs_vides(): void
    {
        Secrets::set('stripe_secret', 'sk_ancienne');
        Settings::flush();

        $this->actingAs($this->admin())->post('/admin/reglages', [
            '_tab' => 'api',
            'apikey_google_maps_key' => 'nouvelle-cle-google',
            'apikey_stripe_secret' => '', // vide = inchangé.
        ])->assertRedirect();

        Settings::flush();
        $this->assertSame('nouvelle-cle-google', Secrets::get('google_maps_key'));
        $this->assertSame('sk_ancienne', Secrets::get('stripe_secret'));
    }

    public function test_masked_ne_revele_pas_la_cle(): void
    {
        Secrets::set('stripe_secret', 'sk_test_tres_longue_cle_9876');
        Settings::flush();

        $masked = Secrets::masked('stripe_secret');
        $this->assertStringContainsString('9876', $masked);
        $this->assertStringNotContainsString('sk_test_tres_longue', $masked);
    }

    public function test_mode_test_live_bascule_les_cles_stripe(): void
    {
        config(['services.stripe.key' => 'pk_test_env']);
        Secrets::set('stripe_key_live', 'pk_live_admin');
        Settings::flush();

        // Mode test (défaut) → clé de test.
        $this->assertSame('pk_test_env', Secrets::stripeKey());

        // Mode live → clé live.
        Settings::set('test_mode', '0');
        Settings::flush();
        $this->assertSame('pk_live_admin', Secrets::stripeKey());
    }

    public function test_endpoint_test_service_protege_et_fonctionnel(): void
    {
        // Non-admin → 403.
        $this->actingAs(User::factory()->create())
            ->postJson('/admin/reglages/test', ['service' => 'stripe'])
            ->assertForbidden();

        // Admin + clé valide (mock HTTP) → ok.
        config(['services.stripe.secret' => 'sk_test_x']);
        Http::fake([
            'api.stripe.com/*' => Http::response(['available' => []]),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/admin/reglages/test', ['service' => 'stripe'])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_endpoint_test_service_utilise_la_valeur_saisie_non_enregistree(): void
    {
        // Aucune clé admin ni .env enregistrée, mais une valeur est fournie (champ tapé, pas encore sauvegardé).
        config(['services.stripe.secret' => '']);
        Http::fake([
            'api.stripe.com/*' => Http::response(['available' => []]),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/admin/reglages/test', [
                'service' => 'stripe',
                'values' => ['stripe_secret' => 'sk_test_pas_encore_enregistree'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // Rien n'a été persisté par le simple fait de tester.
        $this->assertSame('', Secrets::get('stripe_secret'));
    }

    public function test_chaque_onglet_reglages_se_rend_sans_erreur(): void
    {
        $admin = $this->admin();

        foreach (['general', 'tarifs', 'api', 'notifications', 'style'] as $tab) {
            $this->actingAs($admin)->get('/admin/reglages?tab='.$tab)->assertOk();
        }
    }

    /* ── Design ──────────────────────────────────────────────── */

    public function test_style_par_defaut_ne_genere_rien(): void
    {
        $this->assertSame('', Design::overridesCss());
    }

    public function test_accent_surcharge_genere_les_derivees(): void
    {
        Design::save(['--ntb-accent' => '#c4a24d']);
        Settings::flush();

        $css = Design::overridesCss();
        $this->assertStringContainsString('--ntb-accent: #c4a24d;', $css);
        $this->assertStringContainsString('--ntb-accent-hi', $css);
        $this->assertStringContainsString('--ntb-accent-soft', $css);
        $this->assertStringContainsString('.ntb-scope, .ntb-dashboard {', $css);
        $this->assertStringContainsString(':root {', $css); // Miroir Flatpickr.
    }

    public function test_fond_surcharge_derive_les_surfaces(): void
    {
        Design::save(['--ntb-bg' => '#101418']);
        Settings::flush();

        $css = Design::overridesCss();
        $this->assertStringContainsString('--ntb-surf: color-mix(in oklch, #101418, white 7%)', $css);
        $this->assertStringContainsString('--ntb-line: color-mix(in oklch, #101418, white 22%)', $css);
    }

    public function test_rayon_boutons_et_verre_desactive(): void
    {
        Design::save(['ntb_btn_radius' => '12', 'ntb_glass_enabled' => '0']);
        Settings::flush();

        $css = Design::overridesCss();
        $this->assertStringContainsString('--ntb-btn-radius: 12px;', $css);
        $this->assertStringContainsString('.ntb-btn { border-radius: 12px; }', $css);
        $this->assertStringContainsString('--ntb-glass-blur: none;', $css);
    }

    public function test_reset_efface_tokens_et_css_perso(): void
    {
        Design::save(['--ntb-accent' => '#fff']);
        Design::saveCustomCss('.x { color: red; }');
        Settings::flush();

        $this->actingAs($this->admin())->post('/admin/reglages', [
            '_tab' => 'style', '_action' => 'reset',
        ])->assertRedirect();

        Settings::flush();
        $this->assertSame('', Design::overridesCss());
        $this->assertSame('', Design::customCss());
    }

    public function test_style_injecte_dans_le_layout_public(): void
    {
        Design::save(['--ntb-accent' => '#c4a24d']);
        Settings::flush();

        $this->get('/reservation')
            ->assertOk()
            ->assertSee('ntb2-design-overrides', false)
            ->assertSee('--ntb-accent: #c4a24d;', false);
    }

    public function test_css_personnalise_injecte_et_style_echappe(): void
    {
        Design::saveCustomCss('.ntb-recap { border-radius: 18px; }</style><script>alert(1)</script>');
        Settings::flush();

        $response = $this->get('/reservation')->assertOk();
        $response->assertSee('ntb2-custom-css', false);
        $response->assertSee('border-radius: 18px', false);
        // La fermeture </style> injectée a été neutralisée.
        $this->assertStringNotContainsString('</style><script>', $response->getContent());
    }
}
