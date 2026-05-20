# cotiga/spam-guard

Package Laravel — antispam formulaires et gestion des erreurs HTTP.

- Rate limiting par IP
- Filtrage email : TLD, domaine, patterns suspects
- Détection nom charabia, message générique de bot
- Géoblocage (via `stevebauman/location`)
- Log et ban automatique des IPs abusives
- Email d'alerte sur erreurs récurrentes

## Installation

```bash
composer require cotiga/spam-guard
```

## Configuration

```bash
php artisan vendor:publish --tag=spam-guard-config
php artisan migrate
```

Le fichier `config/spam-guard.php` permet de personnaliser tous les seuils, listes de pays, TLD, etc.

## Handler d'exceptions

Dans `app/Exceptions/Handler.php`, étendre la classe du package :

```php
<?php

namespace App\Exceptions;

use Cotiga\SpamGuard\Exceptions\SpamGuardHandler;

class Handler extends SpamGuardHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];
}
```

## Vues d'erreur

Le package fournit une vue `spam-guard::errors.generic` (HTML autonome, sans layout).

Pour la personnaliser avec le layout de votre site :

```bash
php artisan vendor:publish --tag=spam-guard-views
```

Les vues sont copiées dans `resources/views/vendor/spam-guard/errors/`. Vous pouvez aussi créer `resources/views/errors/generic.blade.php` directement dans votre app — elle sera automatiquement prioritaire.

Les vues spécifiques (`errors/404.blade.php`, `errors/500.blade.php`…) restent entièrement dans votre app.

## FormSpamGuard dans les contrôleurs

```php
use Cotiga\SpamGuard\Services\FormSpamGuard;

class ContactController extends Controller
{
    public function store(ContactRequest $request, FormSpamGuard $guard)
    {
        if ($guard->isSpam($request->mel, $request->ip(), $request->only(['nom', 'msg']))) {
            return back()->with('error', 'Votre message n\'a pas pu être envoyé.');
        }
        // ...
    }
}
```

## Tables créées

| Table              | Description                               |
|--------------------|-------------------------------------------|
| `banned_ips`       | IPs bannies automatiquement               |
| `spam_guard_errors`| Erreurs HTTP loguées                      |
| `error_ignored`    | Patterns d'URL à ignorer                  |
| `refused_contacts` | Soumissions de formulaires refusées       |

## Migration depuis une installation manuelle (ex : cotifr)

Si le site dispose déjà des tables `errors`, `banned_ips`, etc., les renommer pour correspondre au package :

```sql
RENAME TABLE `errors` TO `spam_guard_errors`;
-- banned_ips, error_ignored, refused_contacts → noms identiques, aucune action requise
```

Supprimer ensuite les anciens modèles de `app/Models/` (`BannedIp`, `Error`, `ErrorIgnored`, `RefusedContact`) et mettre à jour les `use` dans les contrôleurs.

## Variables d'environnement

```dotenv
SPAM_GUARD_ALERT_EMAIL=support@example.com
SPAM_GUARD_ALERT_THRESHOLD=10
SPAM_GUARD_BAN_THRESHOLD=30
```
