# Fedale RBAC Bundle

A Symfony RBAC bundle inspired by Yii2's auth manager. It mirrors a four-table
model (`auth_item`, `auth_item_child`, `auth_assignment`, `auth_rule`) and maps
those concepts onto native Symfony primitives. It adds a `can()` API for
permissions alongside `isGranted()` for roles, a DB-driven role hierarchy, and
contextual rules (service- or expression-based).

> Not to be confused with `fedale/access-control-bundle`, which is the perimeter
> HTTP guard (path/host/IP firewall). This bundle is RBAC authorization
> (roles/permissions/hierarchy/rules) and is fully independent.

## Concept mapping

| Yii2 RBAC | Here |
|---|---|
| `auth_item` (role/permission, `type` field) | `auth_item` table + `AuthItemType` enum (`role`/`permission`) |
| `auth_item_child` (parent→child hierarchy) | `auth_item_child` table (role→role, role→permission, permission→permission) |
| `auth_assignment` (user→item) | `auth_assignment` table (role **or** permission, including direct-to-user) |
| `auth_rule` (`execute()`) | `auth_rule` table + `RuleInterface` / `ExpressionRule` |
| `Yii::$app->user->can($item, $params)` | `AccessManagerInterface::can($item, $subject)` |
| role→role hierarchy | `RbacRoleHierarchy` decorating `security.role_hierarchy` |

The key expressiveness: you can **assign a permission directly to a single
user** (via `auth_assignment`), bypassing the hierarchy — something Symfony's
roles-only model does not support natively.

## Architecture

- **Assignment** (user→item): `auth_assignment`, the single source of truth (see Token integration).
- **Role→role hierarchy**: `RbacRoleHierarchy` feeds `isGranted(ROLE_*)`.
- **Permissions / decision**: `AccessManager::can($item, $subject)` walks up
  `auth_item_child` gating each node with its `auth_rule`. It is also exposed
  through `DynamicVoter`, so `#[IsGranted('PERMISSION', subject: $obj)]` and
  `isGranted('PERMISSION', $obj)` keep working.

`can()` accepts any item (role or permission). Recommended convention:
`isGranted(ROLE)` for a plain role check; `can(item, $subject)` when you need a
rule's contextual gating.

## RBAC model & NIST positioning

In NIST RBAC (ANSI INCITS 359) terms the bundle covers:

- **Core RBAC (L1)** — users, roles, permissions; permissions acquired through roles,
  plus **direct permission-to-user assignment** (`auth_assignment` accepts a permission, not
  only a role).
- **Hierarchical RBAC (L2), general** — `auth_item_child` is an arbitrary DAG with multiple
  inheritance (role→role, role→permission, permission→permission), not limited to a tree.
- **Beyond static RBAC** — `auth_rule` adds contextual, object-level conditions (service- or
  expression-based on the `$subject`), an ABAC-style capability that plain RBAC lacks.

Not implemented: **Separation of Duty (Constrained RBAC, L3)** — mutually-exclusive roles
(SSD/DSD) and role cardinality are out of scope.

## Installation

```bash
composer require fedale/rbac-bundle
```

Register the bundle (usually automatic with Flex):

```php
// config/bundles.php
return [
    // ...
    Fedale\RbacBundle\FedaleRbacBundle::class => ['all' => true],
];
```

With the Doctrine provider (default) the entities' ORM mapping is registered
automatically: you don't need to add `doctrine.orm.mappings` entries.

## Configuration

```yaml
# config/packages/fedale_rbac.yaml
fedale_rbac:
    enabled: true
    super_admin_role: ROLE_SUPER_ADMIN   # '' to disable the short-circuit
    override_role_hierarchy: true        # decorate security.role_hierarchy (see below)
    inject_assigned_roles: false         # fallback; primary = User::getRoles() (see below)
    cache:
        enabled: true
        pool: cache.app
        ttl: null                        # seconds, or null (manual invalidation)
    provider: doctrine                   # 'doctrine' or a custom storage service id
```

## Database schema (Doctrine provider)

The four tables (FK constraints as shown):

```sql
CREATE TABLE auth_rule (
    name        VARCHAR(64) NOT NULL PRIMARY KEY,
    service_id  VARCHAR(255) NULL,           -- RuleInterface service id
    expression  TEXT NULL,                   -- or an ExpressionLanguage string
    data        JSON NOT NULL DEFAULT ('[]'),
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL
);

CREATE TABLE auth_item (
    name        VARCHAR(64) NOT NULL PRIMARY KEY,
    type        VARCHAR(32) NOT NULL,        -- 'role' | 'permission'
    description TEXT NULL,
    rule_name   VARCHAR(64) NULL,
    data        JSON NOT NULL DEFAULT ('[]'),
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    CONSTRAINT fk_item_rule FOREIGN KEY (rule_name) REFERENCES auth_rule (name)
        ON DELETE SET NULL ON UPDATE CASCADE
);
CREATE INDEX idx_auth_item_type ON auth_item (type);

CREATE TABLE auth_item_child (
    parent VARCHAR(64) NOT NULL,
    child  VARCHAR(64) NOT NULL,
    PRIMARY KEY (parent, child),
    CONSTRAINT fk_child_parent FOREIGN KEY (parent) REFERENCES auth_item (name)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_child_child  FOREIGN KEY (child)  REFERENCES auth_item (name)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE auth_assignment (
    item_name  VARCHAR(64)  NOT NULL,
    user_id    VARCHAR(255) NOT NULL,        -- the Symfony user identifier (e.g. email)
    created_at DATETIME NOT NULL,
    PRIMARY KEY (item_name, user_id),
    CONSTRAINT fk_assignment_item FOREIGN KEY (item_name) REFERENCES auth_item (name)
        ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX idx_auth_assignment_user ON auth_assignment (user_id);
```

Generate the migration with `php bin/console doctrine:migrations:diff` after
installing the bundle.

Notes: a rule references a `service_id` or an `expression` (no serialized PHP
object); timestamps are `DATETIME`; `user_id` is `VARCHAR(255)`.

## Token integration

`auth_assignment` is the single source of truth for user→item. For
`isGranted(ROLE_*)` to reflect assigned roles, choose one mechanism:

### Primary (recommended): `User::getRoles()` reads `auth_assignment`

The `User` entity implements `AssignedRolesAwareInterface`; the
`AssignedRolesUserProvider` decorator injects the roles on every load/refresh
(token always fresh, no session lag).

```php
use Fedale\RbacBundle\Security\AssignedRolesAwareInterface;

class User implements UserInterface, AssignedRolesAwareInterface
{
    /** @var string[] */
    private array $assignedRoles = [];

    public function setAssignedRoles(array $roles): void
    {
        $this->assignedRoles = $roles;
    }

    public function getRoles(): array
    {
        return array_values(array_unique($this->assignedRoles));
    }
}
```

```yaml
# config/services.yaml — decorate your user provider
Fedale\RbacBundle\Security\AssignedRolesUserProvider:
    decorates: 'security.user.provider.concrete.app_user_provider'
    arguments:
        $inner: '@.inner'
```

### Fallback: `inject_assigned_roles: true`

For apps that cannot modify the `User` class. A listener on
`AuthenticationTokenCreatedEvent` enriches the token (handles the standard
login's `PostAuthenticationToken`). With stateful firewalls the injected roles
stay in the session token until re-login; `can()` always reads fresh anyway.

### Migrating from an existing `user_role_assigned` table

If your app already stores role assignments in its own table, migrate them into
`auth_assignment` and drop it:

```sql
-- 1) create the role items if missing
INSERT INTO auth_item (name, type, data, created_at, updated_at)
SELECT DISTINCT ura.role, 'role', '[]', NOW(), NOW()
FROM user_role_assigned ura
LEFT JOIN auth_item ai ON ai.name = ura.role
WHERE ai.name IS NULL;

-- 2) copy the assignments
INSERT INTO auth_assignment (item_name, user_id, created_at)
SELECT ura.role, ura.user_id, NOW() FROM user_role_assigned ura;

-- 3) DROP TABLE user_role_assigned;  (after pointing User::getRoles at the new source)
```

## DB-driven role hierarchy

With `override_role_hierarchy: true` the bundle decorates
`security.role_hierarchy` with `RbacRoleHierarchy`, which expands the role→role
edges from `auth_item_child` (transitive closure). It **replaces** the
`security.yaml` `role_hierarchy`: any static edges defined there must be
mirrored in `auth_item_child`. Permissions do not enter the role hierarchy
(they are resolved by `can()`).

## Rules (`auth_rule`)

A rule attached to an item via `rule_name` runs during `can()` (node gate). Two
forms, both stored in the DB:

### Service rule (`service_id`)

```php
use Fedale\RbacBundle\Contract\RuleInterface;
use Fedale\RbacBundle\Dto\AuthItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

// Auto-tagged 'fedale_rbac.rule' via autoconfigure.
final class AuthorRule implements RuleInterface
{
    public function execute(TokenInterface $token, AuthItem $item, mixed $params = null): bool
    {
        return $params instanceof Post && $params->getAuthor() === $token->getUser();
    }
}
```

`auth_rule.service_id = 'App\Security\AuthorRule'`. A service rule can also
delegate to one of your own voters: `$this->security->isGranted('MY_ATTR', $params)`.

### Expression rule (`expression`)

An ExpressionLanguage string in `auth_rule.expression`, evaluated by
`ExpressionRule`. Variables: `user`, `token`, `subject` (the `$subject` of
`can()`, which may be a **map**), `item`, `roles`, `auth_checker` (for
`is_granted(...)`). Examples:

```
subject.getAuthor() == user
user === subject["author"] and subject["post"].isPublished()
```

> With `#[IsGranted]`, the `attribute` Expression is the condition (→
> `auth_rule.expression`); the `subject:` part that uses `args[...]` stays in the
> controller attribute and produces the `$subject` that reaches the rule.

## Usage

```php
// Controller
use Fedale\RbacBundle\Security\CanTrait;

class InvoiceController extends AbstractController
{
    use CanTrait;

    public function edit(Invoice $invoice): Response
    {
        if (!$this->can('EDIT_INVOICE', $invoice)) {
            throw $this->createAccessDeniedException();
        }
        // ...
    }
}
```

```php
// Or via the native flow (DynamicVoter answers on permission attributes)
#[IsGranted('EDIT_INVOICE', subject: 'invoice')]
public function edit(Invoice $invoice): Response { /* ... */ }
```

In Twig (when `twig/twig` is installed) the `can()` function mirrors the PHP API;
native `is_granted('ROLE_X')` still covers plain role checks:

```twig
{% if can('EDIT_INVOICE', invoice) %}
    <a href="{{ path('invoice_edit', {id: invoice.id}) }}">Edit</a>
{% endif %}
```

Your own voters coexist with `DynamicVoter` (which abstains on non-permission
attributes); use disjoint attributes to avoid double votes.

## Management (write API)

With the Doctrine provider the bundle exposes a write API to manage the graph,
`RbacManagerInterface`. Every mutation flushes and invalidates the affected cache.

```php
public function addItem(string $name, AuthItemType $type, ?string $description = null, ?string $ruleName = null): void;
public function removeItem(string $name): void;
public function addChild(string $parent, string $child): void;
public function removeChild(string $parent, string $child): void;
public function assign(string $userId, string $item): void;
public function revoke(string $userId, string $item): void;
public function addRule(string $name, ?string $serviceId = null, ?string $expression = null): void;
public function removeRule(string $name): void;
```

CLI equivalents (Doctrine provider):

```bash
php bin/console rbac:item:add EDIT_INVOICE --type=permission --description="Edit invoices"
php bin/console rbac:item:add ROLE_EDITOR --type=role
php bin/console rbac:child:add ROLE_EDITOR EDIT_INVOICE
php bin/console rbac:assign gianna EDIT_INVOICE     # direct permission to a user
php bin/console rbac:revoke gianna EDIT_INVOICE
php bin/console rbac:item:remove EDIT_INVOICE
php bin/console rbac:child:remove ROLE_EDITOR EDIT_INVOICE
```

`assign`/`revoke` use the value returned by `getUserIdentifier()` as `user_id`.

The write API invalidates the cache automatically. When you seed the tables
out-of-band (SQL/fixtures), clear the bundle's cache keys explicitly (only the
RBAC keys, not the whole pool; available when the cache is enabled):

```bash
php bin/console rbac:cache:clear
```

## Custom provider (no Doctrine)

Set `provider:` to anything other than `doctrine` and register services for the
three interfaces `ItemStorageInterface`, `AssignmentStorageInterface`,
`RuleStorageInterface`. The rest of the bundle (AccessManager, role hierarchy,
voter, rule resolver) is source-agnostic. The write API and management commands
are Doctrine-only; a custom provider can implement `RbacManagerInterface` itself.

## Read-only commands

```bash
php bin/console rbac:list-items
php bin/console rbac:list-assignments <user>
php bin/console rbac:check <user> <item>   # static reachability (rules NOT evaluated)
```

## Tests

```bash
composer install
vendor/bin/phpunit
```