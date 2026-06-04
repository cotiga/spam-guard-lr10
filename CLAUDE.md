# cotiga/spam-guard-lr10 — Package Laravel (ligne legacy)

## Description

Package antispam et gestion des erreurs HTTP, réutilisable sur tous les sites Laravel COTIGA.

**Ligne legacy gelée** : Laravel 10/11/12, admin **STRAdmin** (fork Voyager, CRUD auto par table — d'où l'absence de classes d'admin dans le package). Pour le socle CotiCMS **Core/Starter** (Laravel 13 + Filament v5), c'est le package `cotiga/spam-guard-cs` qui prend le relais.

- `SpamGuardHandler` : gestion erreurs HTTP (log, ban IP auto, alertes mail)
- `FormSpamGuard` : antispam formulaires (rate limit, géoblocage, TLD, patterns, téléphone...)

## Repo et publication

- **GitHub** : `https://github.com/cotiga/spam-guard-lr10`
- **Packagist** : `cotiga/spam-guard-lr10` — webhook GitHub configuré (auto-update au push)
- **Branche** : `main`

## Workflow de développement

1. Modifier les fichiers ici dans `/Users/boss/GIT/spam-guard-lr10/`
2. Commiter et tagger (`v1.0.x`)
3. Pusher : `git push origin main --tags`
4. Packagist se met à jour automatiquement
5. Sur chaque site concerné : `composer update cotiga/spam-guard-lr10`

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
composer require cotiga/spam-guard-lr10
php artisan migrate
```

**`app/Exceptions/Handler.php`** — étendre SpamGuardHandler :
```php
use Cotiga\SpamGuard\Exceptions\SpamGuardHandler;

class Handler extends SpamGuardHandler { ... }
```

**Contrôleurs avec formulaires** — injecter FormSpamGuard par constructeur :
```php
use Cotiga\SpamGuard\Services\FormSpamGuard;

class MonController extends Controller
{
    public function __construct(private FormSpamGuard $guard) {}

    public function store(MonFormRequest $request)
    {
        if ($this->guard->isSpam($request->mel, $request->ip(), $request->only(['nom', 'tel', 'msg']))) {
            return $this->fakeSuccessResponse($request);
        }

        // traitement normal...

        Session::flash('message', [
            'bg' => 'bg-success',
            'delai' => '9000',
            'text' => '<h2>Merci '.ucfirst($request->nom).',</h2><p>Nous vous répondrons dès réception.</p>',
        ]);
        return redirect('/');
    }

    private function fakeSuccessResponse(MonFormRequest $request)
    {
        Session::flash('message', [
            'bg' => 'bg-info',
            'delai' => '7000',
            'text' => '<h2>Merci '.ucfirst($request->nom).' !</h2>'
                .'<p class="mb-0">Votre message a bien été pris en compte.</p>',
        ]);
        return redirect('/');
    }
}
```

- Message fake : `bg-info` (bleu) + formulation vague, sans promesse de réponse
- Message réel : `bg-success` (vert) + formulation complète
- IP : toujours `$request->ip()`, jamais `$_SERVER`

## Erreurs fréquentes à ne pas reproduire

| Erreur | Symptôme | Correct |
|--------|----------|---------|
| `use Cotiga\SpamGuard\SpamGuardHandler` | 500 sans log Laravel (fatal PHP au bootstrap) | `use Cotiga\SpamGuard\Exceptions\SpamGuardHandler` |
| `$guard->check($request)` | Erreur "method not found" | `$guard->isSpam($email, $ip, $fields)` |
| `$this->fakeSuccessResponse()` | Erreur "method not found" | Flash + redirect (voir exemple ci-dessus) |
| `$_SERVER['HTTP_X_FORWARDED_FOR']` | Code fragile, non testé | `$request->ip()` |

## Règles importantes

- NE PAS publier les migrations (`vendor:publish --tag=spam-guard-migrations`) — le package les charge automatiquement via `loadMigrationsFrom`
- NE PAS ajouter `composer install` ni `php artisan migrate` dans les hooks de déploiement — trop risqué sur mutualisé, à faire manuellement en SSH après chaque déploiement concerné
- `composer install --no-dev` en production
