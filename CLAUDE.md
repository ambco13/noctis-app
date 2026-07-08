# CLAUDE.md — noctis-app

Application de réservation VTC/taxi, refonte standalone (Laravel, sans WordPress)
du plugin `noctis-taxi-booking-v2`. Parité fonctionnelle complète visée.
Voir `README.md` pour l'architecture, les pages et le principe des clés API.

## Environnement local (Windows, pas d'admin)

Aucun outil n'est installé globalement — tout est en local dans `tools/`, et
MariaDB n'a pas de service Windows (session non-admin). À chaque session :

```powershell
# 1. Démarrer MariaDB manuellement (sinon "connection refused")
Start-Process "C:\Program Files\MariaDB 12.3\bin\mariadbd.exe" `
  -ArgumentList '--datadir="C:\Users\lagou\tools\mariadb-data"' -WindowStyle Hidden

# 2. PHP 8.4 + Composer sont dans :
C:\Users\lagou\tools\php84\php.exe
C:\Users\lagou\tools\php84\composer.phar
```

- Base `noctis`, utilisateur `noctis`@localhost — mot de passe dans `.env`
  (`DB_PASSWORD`), ne jamais le dupliquer ailleurs (ce fichier est versionné).
- `php artisan serve` doit être **redémarré après toute modif de `php.ini`**
  (ex. CA bundle cURL) : un process PHP déjà lancé ne recharge pas `php.ini`.

## Règles de travail établies

- **Tests avant commit, toujours** : `php artisan test` (95 tests actuellement,
  SQLite en mémoire — n'attrape pas les erreurs strictes MariaDB comme
  `ONLY_FULL_GROUP_BY`, donc vérifier aussi via `artisan tinker` sur de vraies
  requêtes Eloquent complexes avant de les considérer sûres).
- **Parité avec le plugin d'origine** = référence de comportement, pas
  seulement d'apparence. En cas de doute sur un design/comportement, vérifier
  dans `C:\Users\lagou\Local Sites\test1\app\public\wp-content\plugins\noctis-taxi-booking-v2`
  avant de décider — ne pas supposer qu'un changement visuel est un bug sans
  comparer au comportement d'origine.
- **Contre-vérifier les rapports externes** (audits/diagnostics collés par
  l'utilisateur) contre le code réel avant d'appliquer leurs correctifs —
  plusieurs se sont révélés partiellement obsolètes ou basés sur un composant
  différent de celui visé.
- **Secrets** (`app/Support/Secrets.php`) : priorité valeur admin (chiffrée
  via `Crypt`/`APP_KEY`) > `.env`. Ne jamais logger ni afficher une clé en
  clair côté admin (`Secrets::masked()`).
- **Piège CSS grid récurrent** : un item de grille (`display: grid`) a
  `min-width: auto` par défaut et refuse de rétrécir sous la largeur de son
  contenu (texte non coupable : email, adresse...) — cause de plusieurs
  débordements horizontaux constatés. Toujours `min-width: 0` (ou
  `minmax(0, 1fr)` pour les colonnes) sur les items de grille contenant du
  texte imprévisible en longueur.

## Comptes

- Admin back-office créé via `php artisan noctis:make-admin <email>`.
