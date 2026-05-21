# cotiga/spam-guard

Package Laravel — antispam formulaires et gestion des erreurs HTTP.

- Rate limiting par IP
- Filtrage email : TLD, domaine, patterns suspects
- Détection nom avec chiffres, nom charabia, message générique de bot, message charabia
- Validation téléphone (format date, lettres, longueur hors norme)
- Géoblocage (via `stevebauman/location`)
- Log et ban automatique des IPs abusives
- Email d'alerte sur erreurs récurrentes

## Installation

```bash
composer require cotiga/spam-guard
php artisan migrate
```

## Configuration

```bash
php artisan vendor:publish --tag=spam-guard-config
```

Le fichier `config/spam-guard.php` permet de personnaliser tous les seuils, listes de pays, TLD, patterns, etc.

## Handler d'exceptions

Dans `app/Exceptions/Handler.php`, étendre la classe du package :

```php
<?php

namespace App\Exceptions;

use Cotiga\SpamGuard\Exceptions\SpamGuardHandler;
use Throwable;

class Handler extends SpamGuardHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
```

## FormSpamGuard dans les contrôleurs

```php
use Cotiga\SpamGuard\Services\FormSpamGuard;

class ContactController extends Controller
{
    public function store(ContactRequest $request, FormSpamGuard $guard)
    {
        if ($guard->isSpam($request->mel, $request->ip(), $request->only(['nom', 'tel', 'msg']))) {
            return $this->fakeSuccessResponse($request);
        }
        // traitement normal...
    }
}
```

## Vues d'erreur

Le package fournit une vue `spam-guard::errors.generic` (HTML autonome, sans layout).

Pour la personnaliser avec le layout de votre site :

```bash
php artisan vendor:publish --tag=spam-guard-views
```

Les vues sont copiées dans `resources/views/vendor/spam-guard/errors/`. Vous pouvez aussi créer `resources/views/errors/generic.blade.php` directement dans votre app — elle sera automatiquement prioritaire.

## Tables créées

| Table                         | Description                           |
|-------------------------------|---------------------------------------|
| `spam_guard_banned_ips`       | IPs bannies automatiquement           |
| `spam_guard_errors`           | Erreurs HTTP loguées                  |
| `spam_guard_error_ignoreds`   | Patterns d'URL à ignorer              |
| `spam_guard_refused_contacts` | Soumissions de formulaires refusées   |

## Variables d'environnement

```dotenv
SPAM_GUARD_ALERT_EMAIL=support@example.com
SPAM_GUARD_ALERT_THRESHOLD=10
SPAM_GUARD_BAN_THRESHOLD=30
```
