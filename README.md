# Fedale Access Control Voter Bundle

Autorizzazione **nativa Symfony** (`#[IsGranted]` / `isGranted()` / `AccessDecisionManager`) guidata da
regole **dinamiche e persistite su database**.

È il complemento di [`fedale/access-control-bundle`](https://packagist.org/packages/fedale/access-control-bundle):

| | `access-control-bundle` | `access-control-voter-bundle` (questo) |
|---|---|---|
| Livello | Firewall su `kernel.request` | Voter nativo Symfony |
| Protegge | URL (path/host/ip/method) | Attributi/azioni (`#[IsGranted('EDIT_INVOICE')]`) |
| Semantica | first-match-wins | first-match-wins |
| Sorgente regole | DB (Doctrine) + cache | DB (Doctrine) + cache |

Questo bundle **dipende** da `access-control-bundle` e ne riusa i pattern (provider, cache PSR-6,
bridge Doctrine, config `AbstractBundle`), ma **non lo modifica** e vive in un pacchetto separato.

## Installazione

```bash
composer require fedale/access-control-voter-bundle
```

Registra il bundle (se non usi Symfony Flex):

```php
// config/bundles.php
return [
    // ...
    Fedale\AccessControlVoterBundle\FedaleAccessControlVoterBundle::class => ['all' => true],
];
```

Col provider Doctrine (default) il mapping ORM dell'entità viene registrato **automaticamente**
(`prependExtension`): non serve aggiungere voci a `doctrine.orm.mappings`. Genera ed esegui la
migration per creare la tabella `permission_rule`:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Quick start

1. Annota un controller/azione con un attributo a piacere:

   ```php
   use Symfony\Component\Security\Http\Attribute\IsGranted;

   #[IsGranted('EDIT_INVOICE')]
   public function edit(Invoice $invoice): Response { /* ... */ }
   ```

2. Inserisci una regola nella tabella `permission_rule` (o via la tua UI/CRUD):

   | attribute | roles | allow | sort | active |
   |---|---|---|---|---|
   | `EDIT_INVOICE` | `["ROLE_EDITOR"]` | `true` | `0` | `true` |

3. Un utente con `ROLE_EDITOR` ottiene **200**, gli altri **403**. Disattiva la regola
   (`active = false`) o cambiala a runtime: la decisione cambia senza deploy
   (ricordati di invalidare il pool di cache, vedi sotto).

### Come decide il voter

Per ogni attributo (`EDIT_INVOICE`):

1. **`super_admin_role`** concesso → `ACCESS_GRANTED` (short-circuit).
2. Regole attive dell'attributo, ordinate per `sort` ASC: la **prima applicabile** (ruoli soddisfatti,
   `roles` vuoto = sempre applicabile) decide via il suo `allow` → grant/deny. *(first-match-wins)*
3. Nessuna regola per quell'attributo → `supports()` è `false` → **ABSTAIN** (decidono gli altri voter).
4. Regole presenti ma nessuna applicabile all'utente → `ACCESS_DENIED`.

I ruoli sono valutati con `isGranted()`, quindi la `role_hierarchy` dell'app è rispettata.

> Edge-case: evita di usare come `attribute` una stringa che coincide con un ruolo (`ROLE_*`): il voter
> è attributo-scoped proprio per non intercettare i voti sui ruoli ed evitare ricorsione.

## Configurazione

```yaml
# config/packages/fedale_access_control_voter.yaml
fedale_access_control_voter:
    enabled: true
    # Ruolo che bypassa tutte le regole (stringa vuota per disabilitare).
    super_admin_role: ROLE_SUPER_ADMIN
    cache:
        enabled: true
        pool: cache.app
        # ttl: 3600   # secondi; null = nessuna scadenza (invalida il pool a mano)
    # 'doctrine' = provider built-in, oppure l'id di un servizio custom.
    provider: doctrine
```

Le regole sono cachate **in blocco** sotto un'unica chiave
(`CachedPermissionRuleProvider::CACHE_KEY`): il voter le interroga ad ogni voto, quindi il DB non viene
toccato per ogni richiesta. Dopo aver modificato le regole, invalida il pool (es.
`php bin/console cache:pool:clear cache.app`) oppure imposta un `ttl`.

Diagnostica delle regole effettive:

```bash
php bin/console fedale:access-control-voter:list
```

## Provider custom

Imposta `provider` all'id di un servizio che implementa
`Fedale\AccessControlVoterBundle\Contract\PermissionRuleProviderInterface` (YAML, API, in-memory, ...).
La decorazione di cache resta opzionale e indipendente dalla sorgente.

## Ispirazione: Yii2 RBAC

Il design prende spunto dall'auth manager di Yii2 e ne mappa i concetti su quelli nativi di Symfony:

| Yii2 RBAC | Qui |
|---|---|
| Permission (auth item) | `attribute` (es. `EDIT_INVOICE`) |
| `Yii::$app->user->can($permission, $params)` | `isGranted($attribute, $subject)` |
| `$params` passato a `can()` | `$subject` del voter |
| Assignment ruolo→utente, role hierarchy | `roles` sulla regola + `role_hierarchy` di Symfony |
| **Rule** (`execute($user, $item, $params): bool`) | `PermissionConditionInterface::evaluate($subject, $token, $context)` |

L'idea più utile di Yii2 è la **Rule**: una condizione contestuale e riusabile, agganciata a un permesso,
che decide in base all'oggetto (es. "puoi modificare *questa* fattura solo se ne sei l'autore").

### Predisposizione object-level (non ancora attiva)

L'entità/DTO espongono già due campi pensati per questo:

- `subjectType` — FQCN del soggetto a cui la regola si applica;
- `condition` — id di un servizio `PermissionConditionInterface` (la "Rule" stile Yii2).

```php
interface PermissionConditionInterface
{
    public function evaluate(mixed $subject, TokenInterface $token, array $context = []): bool;
}
```

Allo stato attuale il `DynamicVoter` **legge** questi campi ma **non** li valuta: la decisione dipende
dai soli `roles`. La valutazione del `$subject` e l'esecuzione delle condizioni sono predisposte ma
fuori scope (estensione futura), insieme a un eventuale supporto per ExpressionLanguage.

## Test

```bash
composer install
vendor/bin/phpunit
```
