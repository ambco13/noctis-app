# NOCTIS Taxi — Application de réservation VTC

Application web autonome de réservation de taxi / VTC : estimation de prix
(Google Routes API), paiement en ligne (Stripe & PayPal), notifications
(e-mail & SMS Twilio), espace client (magic link + mot de passe) et
back-office complet. Refonte standalone du plugin WordPress
`noctis-taxi-booking-v2`, à parité fonctionnelle, sans WordPress.

- **Stack :** Laravel 13 · PHP 8.4 · MySQL/MariaDB
- **Langue :** Français
- **Devise par défaut :** Euro (€)

---

## 1. Pages

| URL | Rôle |
|-----|------|
| `/reservation` | Étape 1 : formulaire d'estimation (départ, arrivée, date, heure) |
| `/ma-reservation` | Étapes 2-4 : choix du véhicule, coordonnées, paiement, confirmation |
| `/mon-compte` | Espace client : connexion (mot de passe ou magic link), réservations, profil, RGPD |
| `/admin` | Back-office : dashboard, réservations, clients, véhicules, réglages |

## 2. Installation

```bash
git clone <repo> noctis-app && cd noctis-app
composer install
cp .env.example .env
php artisan key:generate
```

Configurer la base dans `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`),
puis :

```bash
php artisan migrate --seed     # tables + 2 véhicules d'exemple + réglages
php artisan storage:link       # images des véhicules
php artisan noctis:make-admin votre@email.fr   # compte back-office
```

Lancement en développement :

```bash
php artisan serve              # http://127.0.0.1:8000
php artisan queue:work         # OBLIGATOIRE pour l'envoi des notifications
```

## 3. Clés API

Deux façons de configurer, par ordre de priorité :

1. **Back-office** — *Réglages → API & Intégrations* : clés chiffrées en base
   (via `APP_KEY`), boutons « Tester » par service, mode test/live.
2. **`.env`** — utilisé en secours quand aucune valeur admin n'existe :

| Variable | Service |
|----------|---------|
| `GOOGLE_MAPS_API_KEY` | Google Routes + Places (calcul de trajet, autocomplétion) |
| `STRIPE_KEY` / `STRIPE_SECRET` | Stripe **test** (pk_test…, sk_test…) |
| `STRIPE_KEY_LIVE` / `STRIPE_SECRET_LIVE` | Stripe **live** |
| `STRIPE_WEBHOOK_SECRET` | Signature du webhook (whsec…) |
| `PAYPAL_CLIENT_ID` / `PAYPAL_SECRET` | PayPal **sandbox** |
| `PAYPAL_CLIENT_ID_LIVE` / `PAYPAL_SECRET_LIVE` | PayPal **live** |
| `TWILIO_SID` / `TWILIO_TOKEN` / `TWILIO_FROM` | SMS |

Le réglage **Mode test** (Réglages → API) choisit la paire test ou live pour
Stripe et PayPal.

**Webhook Stripe** : déclarer `https://votre-domaine/api/v1/webhook/stripe`
dans le dashboard Stripe (événements `payment_intent.succeeded` et
`payment_intent.payment_failed`) et reporter le secret `whsec_…`.

## 4. Architecture

```
app/
├── Http/Controllers/
│   ├── Api/            # Tunnel : quotes, booking, paiements, auth client, profil
│   ├── Admin/          # Back-office : dashboard, réservations, véhicules, réglages
│   ├── BookingPageController.php   # Pages du tunnel (étape 1 → étapes 2-4)
│   └── AccountPageController.php   # Espace client
├── Services/           # Cœur métier porté du plugin
│   ├── Pricing.php         # base + km + min, plancher, majorations nuit/WE/fériés
│   ├── GoogleMaps.php      # Routes API + Places (proxy serveur, cache 12 h)
│   ├── BookingService.php  # création pending, confirmation idempotente
│   ├── Stripe.php          # PaymentIntent, webhook (HMAC + anti-rejeu)
│   ├── PayPal.php          # Orders API v2 (create/capture)
│   ├── MagicLink.php       # tokens hashés, consommation atomique
│   ├── CustomerService.php # comptes, historique emails, RGPD
│   └── Notifications.php   # e-mails HTML + SMS, templates à variables
├── Support/
│   ├── Settings.php    # réglages (table settings, cache requête)
│   ├── Secrets.php     # clés API chiffrées (admin > .env), mode test/live
│   └── Design.php      # panneau Style : tokens --ntb-*, CSS généré
public/assets/          # CSS/JS portés du plugin (ntb-public.js, etc.)
public/vendor/          # Flatpickr, Leaflet (embarqués, pas de CDN)
```

Principes de sécurité hérités du plugin et conservés :

- **Prix recalculé côté serveur** — jamais de confiance au montant du client.
- **Paiement vérifié serveur** : l'intent/ordre doit être celui lié à la
  réservation (`transaction_id`), et son statut vérifié auprès de la passerelle.
- **Webhook Stripe** : signature HMAC + fenêtre anti-rejeu 300 s + idempotence
  at-most-once (table `stripe_events`, marquage avant traitement).
- **Auth client** : réponses anti-énumération identiques, rate limiting par
  e-mail **et** par IP, magic links à usage unique (hash SHA-256, TTL 24 h,
  consommation en transaction verrouillée).
- **RGPD** : export JSON, suppression anonymisante (bloquée si réservations
  futures confirmées).
- Les clés API ne transitent jamais vers le navigateur ; Google/Stripe/PayPal
  sont appelés uniquement côté serveur (le front ne reçoit que la clé
  publiable Stripe).

## 5. Tests

```bash
php artisan test           # 94 tests (pricing, webhooks, paiements, auth, admin…)
./vendor/bin/pint --dirty  # formatage
```

Les tests tournent sur SQLite en mémoire (aucun impact sur la base).

## 6. Mise en production — liste de contrôle

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
- [ ] HTTPS obligatoire + cookies de session sécurisés
      (`SESSION_SECURE_COOKIE=true`)
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Worker de file d'attente supervisé (`php artisan queue:work` via
      systemd/Supervisor) — sans lui, aucune notification ne part
- [ ] `MAIL_MAILER` configuré (SMTP réel — en dev les mails vont dans le log)
- [ ] Mode test désactivé (Réglages → API) + clés live renseignées + webhook
      Stripe déclaré sur le domaine de production
- [ ] Sauvegardes de la base ; les clés admin sont chiffrées avec `APP_KEY` :
      sauvegarder le `.env` séparément, sinon elles sont irrécupérables
- [ ] Vérifier que l'hébergeur supporte SSH/Composer/CLI et un processus
      persistant (`queue:work`) — un hébergement mutualisé gratuit type
      InfinityFree ne le permet généralement pas
- [ ] `TrustProxies` (`bootstrap/app.php`) à jour avec les IP du proxy réel
      devant l'app (configuré pour Cloudflare actuellement — voir
      https://www.cloudflare.com/ips/)
