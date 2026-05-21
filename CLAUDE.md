# cotiga/spam-guard — Package Laravel

## Description

Package antispam et gestion des erreurs HTTP, réutilisable sur tous les sites Laravel COTIGA.

- `SpamGuardHandler` : gestion erreurs HTTP (log, ban IP auto, alertes mail)
- `FormSpamGuard` : antispam formulaires (rate limit, géoblocage, TLD, patterns, téléphone...)

## Repo et publication

- **GitHub** : `https://github.com/cotiga/cotiga-spam-guard`
- **Packagist** : `cotiga/spam-guard` — webhook GitHub configuré (auto-update au push)
- **Branche** : `master`

## Workflow de développement

1. Modifier les fichiers ici dans `/Users/boss/GIT/spam-guard/`
2. Commiter et tagger (`v1.0.x`)
3. Pusher : `git push origin master --tags`
4. Packagist se met à jour automatiquement
5. Sur chaque site concerné : `composer update cotiga/spam-guard`

## Versioning

Versions gérées par **tags git** uniquement (pas dans `composer.json`).
Version actuelle : **v1.0.4**

## Structure

```
config/spam-guard.php          # Config publiable (seuils, listes pays/TLD, patterns...)
database/migrations/           # Migration unique — 4 tables spam_guard_*
resources/views/errors/        # Vue générique d'erreur
src/
├── Exceptions/SpamGuardHandler.php   # Handler HTTP à étendre dans les apps
├── Models/                           # BannedIp, HttpError, ErrorIgnored, RefusedContact
├── Services/FormSpamGuard.php        # Service antispam formulaires
└── SpamGuardServiceProvider.php
```

## Tables créées

| Table                        | Description                          |
|------------------------------|--------------------------------------|
| `spam_guard_banned_ips`      | IPs bannies automatiquement          |
| `spam_guard_errors`          | Erreurs HTTP loguées                 |
| `spam_guard_error_ignoreds`  | Patterns d'URL à ignorer             |
| `spam_guard_refused_contacts`| Soumissions de formulaires refusées  |

## Sites utilisant ce package

| Site     | État                        |
|----------|-----------------------------|
| cotifr   | v1.0.3 installé, prod ✓     |
| nordfeld | v1.0.4 à déployer           |

## Installation sur un nouveau site

```bash
composer require cotiga/spam-guard
php artisan migrate
```

**`app/Exceptions/Handler.php`** — étendre SpamGuardHandler :
```php
use Cotiga\SpamGuard\Exceptions\SpamGuardHandler;

class Handler extends SpamGuardHandler { ... }
```

**Contrôleurs avec formulaires** — injecter FormSpamGuard :
```php
use Cotiga\SpamGuard\Services\FormSpamGuard;

public function store(Request $request, FormSpamGuard $guard)
{
    if ($guard->isSpam($request->mel, $request->ip(), $request->only(['nom', 'tel', 'msg']))) {
        return $this->fakeSuccessResponse($request);
    }
}
```

## Règles importantes

- NE PAS publier les migrations (`vendor:publish --tag=spam-guard-migrations`) — le package les charge automatiquement via `loadMigrationsFrom`
- NE PAS ajouter `composer install` ni `php artisan migrate` dans les hooks de déploiement — trop risqué sur mutualisé, à faire manuellement en SSH après chaque déploiement concerné
- `composer install --no-dev` en production
