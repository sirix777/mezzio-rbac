# Mezzio RBAC
[![Latest Stable Version](http://poser.pugx.org/sirix/mezzio-rbac/v)](https://packagist.org/packages/sirix/mezzio-rbac) [![Total Downloads](http://poser.pugx.org/sirix/mezzio-rbac/downloads)](https://packagist.org/packages/sirix/mezzio-rbac) [![Latest Unstable Version](http://poser.pugx.org/sirix/mezzio-rbac/v/unstable)](https://packagist.org/packages/sirix/mezzio-rbac) [![License](http://poser.pugx.org/sirix/mezzio-rbac/license)](https://packagist.org/packages/sirix/mezzio-rbac) [![PHP Version Require](http://poser.pugx.org/sirix/mezzio-rbac/require/php)](https://packagist.org/packages/sirix/mezzio-rbac)

RBAC authorization package for Mezzio framework with attribute support.

> **Pre-1.0 package:** Not yet production-ready. Public API and configuration may change with breaking changes before `1.0.0`.

## Installation

```bash
composer require sirix/mezzio-rbac
```

Package is auto-registered via `extra.laminas.config-provider`.

## Core Concepts

### Actor

Current subject is represented by `ActorInterface`.

```php
use Sirix\Mezzio\Rbac\Actor\Actor;

$actor = new Actor(['editor', 'moderator']);
```

Guest fallback is provided by `Sirix\Mezzio\Rbac\Actor\GuestActor`.

### Guard

Main authorization entrypoint:

```php
use Sirix\Mezzio\Rbac\Contract\GuardInterface;

$guard->allows('posts.update');
$guard->denies('admin.panel');
$guard->authorize('posts.delete');
```

`authorize()` throws `Sirix\Mezzio\Rbac\Exception\AuthorizationException` with HTTP status `403`.

### Permissions

Permissions use dot-notation and wildcard matching:

- `posts.read`
- `posts.update`
- `admin.users.delete`
- `posts.*` (greedy match)
- `admin.*.delete` (exact segment count)

Example:

```php
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;

$permissions->addRole('editor');
$permissions->associate('editor', 'posts.*');
$permissions->associate('editor', 'posts.delete', ForbidRule::class);
```

Resolution rules:

- exact match beats wildcard
- more specific wildcard beats broader wildcard
- latest association wins when specificity is equal
- another actor role may still grant access if one role forbids it

### Conflict Resolution (Allow wins over Deny)

The package follows an "Allow wins over Deny" policy. If an actor has multiple roles, access is granted if **at least one** role allows the permission.

Example: If a user has both `user` (allowed `posts.read`) and `banned` (forbidden `posts.read`) roles, the user **will still have access** because the `user` role grants it.

### Wildcard Matching

Permissions use dot-notation and support "greedy" wildcard matching:

- `posts.*` matches `posts.read`, `posts.update`, and also nested resources like `posts.read.history`.
- `admin.*` grants access to all sub-resources of any depth.
- Non-terminal wildcards (e.g., `admin.*.delete`) still require exact segment positioning.

## Rules

Built-in rules:

- `Sirix\Mezzio\Rbac\Rule\AllowRule`
- `Sirix\Mezzio\Rbac\Rule\ForbidRule`

Custom rules implement `Sirix\Mezzio\Rbac\Contract\RuleInterface`:

```php
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;

final class OwnPostRule implements RuleInterface
{
    public function allows(ActorInterface $actor, string $permission, array $context): bool
    {
        return ($context['ownerId'] ?? null) === ($context['userId'] ?? null);
    }
}
```

Then associate it with a permission:

```php
$permissions->associate('user', 'posts.update', OwnPostRule::class);
```

## HTTP Integration

### With standard Mezzio routing

Register `AuthorizeMiddleware` in your route pipeline and set permission as a request attribute. You can do this by passing an array of middleware to the route. Using `RbacAttribute` enum ensures your code is decoupled from the `#[Can]` attribute:

```php
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;

    $app->post('/posts/:id',
        [
            AuthorizeMiddleware::class,
            \Handler\PostHandler::class,
        ],
        'post.update')
        ->setOptions([
            'defaults' => [
                RbacAttribute::Permission->value => 'posts.update',
                RbacAttribute::Context->value => ['post' => 'id'],
            ],
        ]);
```

Or if you use global `AuthorizeMiddleware`, you can set attributes on the route:

```php
use Sirix\Mezzio\Rbac\RbacAttribute;

$app->post('/posts/:id', PostHandler::class, 'post.update')
    ->setOptions([
        'defaults' => [
            RbacAttribute::Permission->value => 'posts.update',
        ],
    ]);
```

### With `sirix/mezzio-routing-attributes` (Optional)

When used together with `sirix/mezzio-routing-attributes`, the provided attribute (like `#[Can]`) implements `RouteAttributeModifierInterface`, which is processed during route extraction. This automatically injects `AuthorizeMiddleware` into the route pipeline and passes permission/context as request defaults:

```php
use Sirix\Mezzio\Rbac\Attribute\Can;
use Sirix\Mezzio\Routing\Attributes\Attribute\Post;

#[Post('/posts/:id', name: 'post.update')]
#[Can('posts.update', ['post' => 'id'])]
final class PostHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // ...
    }
}
```

No manual middleware registration is needed.

### Manual authorization

You can also authorize from services or handlers directly:

```php
use Sirix\Mezzio\Rbac\Contract\GuardInterface;

final readonly class PostService
{
    public function __construct(private GuardInterface $guard) {}

    public function deletePost(string $postId): void
    {
        $this->guard->authorize('posts.delete', [
            'postId' => $postId,
        ]);
    }
}
```

## Storage Boundary

The package depends on contracts, not on concrete persistence.

Public storage contract:

- `Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface`

Default implementation:

- `Sirix\Mezzio\Rbac\InMemoryPermissionStore`

That means later adapters can replace storage without changing the guard API.

## Extensibility

The package is built on PSR-compliant and internal contracts, allowing you to swap almost any component.

### Custom Actor Provider

By default, the package uses `ActorProviderFactory` which creates an `ActorProvider` with a `GuestActor`. In a real application, you'll likely want to fetch the current user from your authentication service:

```php
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;

final readonly class MyActorProvider implements ActorProviderInterface
{
    public function __construct(private MyAuthService $auth) {}

    public function getActor(): ActorInterface
    {
        $user = $this->auth->getIdentity();
        
        return new Actor($user?->getRoles() ?? []);
    }
}
```

Register it in your dependencies:

```php
'dependencies' => [
    'factories' => [
        ActorProviderInterface::class => MyActorProviderFactory::class,
    ],
],
```

### Custom Permission Store

While `InMemoryPermissionStore` is great for testing or small apps with config-based RBAC, you can implement `PermissionStoreInterface` to load permissions from a Database or Redis:

```php
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;

final readonly class DatabasePermissionStore implements PermissionStoreInterface
{
    public function __construct(private PDO $pdo) {}

    public function associationsForRole(string $role): array
    {
        // Fetch from DB and map to PermissionAssociation objects
    }
    
    // ... implement other methods
}
```

### Custom Rules

As shown in the [Rules](#rules) section, you can implement `RuleInterface` to add dynamic logic to your permissions. Rules are resolved via the `RuleResolver`, which by default uses the PSR Container to instantiate rule classes.

## Main Components

- `Guard`
- `Permissions`
- `PermissionMatcher`
- `RuleResolver`
- `InMemoryPermissionStore`
- `AuthorizeMiddleware`
- `RbacAttribute` (Enum for request attributes)
- `#[Can(...)]`
