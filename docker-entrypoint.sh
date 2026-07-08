#!/bin/sh
# Démarrage du conteneur Render : migrations + caches, puis le serveur.
# Le disque n'est pas persistant sur le plan gratuit Render (web service) :
# tout ce qui est écrit dans storage/ (images véhicules uploadées) disparaît
# au prochain déploiement/redémarrage. Acceptable pour un test, pas pour la prod.
set -e

php artisan storage:link || true
php artisan migrate --force
# --seed : VehicleSeeder et SettingSeeder sont idempotents (no-op si des
# données existent déjà), donc sûr à relancer à chaque démarrage.
php artisan db:seed --force

# Compte admin optionnel, piloté par variable d'env (pas de Shell/CLI requis
# sur Render). Idempotent (promeut ou crée), mais réécrit le mot de passe à
# CHAQUE démarrage si ADMIN_PASSWORD reste défini : retire ces deux variables
# de l'environnement Render une fois le compte créé, sinon un redémarrage
# écrasera un mot de passe changé depuis via l'interface admin.
if [ -n "$ADMIN_EMAIL" ]; then
    if [ -n "$ADMIN_PASSWORD" ]; then
        php artisan noctis:make-admin "$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"
    else
        php artisan noctis:make-admin "$ADMIN_EMAIL"
    fi
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
