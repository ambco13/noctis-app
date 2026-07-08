#!/bin/sh
# Démarrage du conteneur Render : migrations + caches, puis le serveur.
# Le disque n'est pas persistant sur le plan gratuit Render (web service) :
# tout ce qui est écrit dans storage/ (images véhicules uploadées) disparaît
# au prochain déploiement/redémarrage. Acceptable pour un test, pas pour la prod.
set -e

php artisan storage:link || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
