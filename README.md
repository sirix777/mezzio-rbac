# Mezzio RBAC

[![Latest Stable Version](http://poser.pugx.org/sirix/mezzio-rbac/v)](https://packagist.org/packages/sirix/mezzio-rbac) [![Total Downloads](http://poser.pugx.org/sirix/mezzio-rbac/downloads)](https://packagist.org/packages/sirix/mezzio-rbac) [![Latest Unstable Version](http://poser.pugx.org/sirix/mezzio-rbac/v/unstable)](https://packagist.org/packages/sirix/mezzio-rbac) [![License](http://poser.pugx.org/sirix/mezzio-rbac/license)](https://packagist.org/packages/sirix/mezzio-rbac) [![PHP Version Require](http://poser.pugx.org/sirix/mezzio-rbac/require/php)](https://packagist.org/packages/sirix/mezzio-rbac)

RBAC authorization package for Mezzio with PSR-15 middleware and optional PHP attribute integration.

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

### Authorization paths

The package has two authorization paths:

| Use case | Service |
|----------|---------|
| HTTP request authorization | `RequestGuardInterface` / `AuthorizeMiddleware` |
| Non-HTTP service or CLI authorization | `GuardInterface` |

`GuardInterface` remains request-independent. It uses `ActorProviderInterface` and is useful from services, CLI commands, or application-managed contexts.

`AuthorizeMiddleware` uses `RequestGuardInterface` so it can authorize against the actor stored on the current PSR-7 request.

### Guard

Main non-HTTP authorization entrypoint:

```php
use Sirix\Mezzio\Rbac\Contract\GuardInterface;

$guard->allows('posts.update');
$guard->denies('admin.panel');
$guard->authorize('posts.delete');
```

`authorize()` throws `Sirix\Mezzio\Rbac\Exception\AuthorizationException` with HTTP status `403`.

### Request Guard

HTTP-aware authorization entrypoint:

```php
use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;

final readonly class PostHandler
{
    public function __construct(private RequestGuardInterface $guard) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->authorize($request, 'posts.update', [
            'postId' => $request->getAttribute('id'),
        ]);

        // ...
    }
}
```

For route-level protection, prefer `AuthorizeMiddleware` or `#[Can]`.

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

- exact match beats wildcard;
- more specific wildcard beats broader wildcard;
- latest association wins when specificity is equal;
- another actor role may still grant access if one role forbids it.

### Conflict Resolution: Allow wins over Deny

The package follows an "Allow wins over Deny" policy. If an actor has multiple roles, access is granted if **at least one** role allows the permission.

Example: if a user has both `user` allowed `posts.read` and `banned` forbidden `posts.read`, the user still has access because the `user` role grants it.

### Wildcard Matching

Permissions use dot-notation and support greedy terminal wildcard matching:

- `posts.*` matches `posts.read`, `posts.update`, and nested resources like `posts.read.history`.
- `admin.*` grants access to all sub-resources of any depth.
- Non-terminal wildcards, for example `admin.*.delete`, still require exact segment positioning.

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

### Actor resolution for HTTP requests

`AuthorizeMiddleware` resolves the actor from the current request through `RequestActorProviderInterface`.

The default provider reads this request attribute:

```php
'rbac' => [
    'request_actor_attribute' => 'sirix.authentication.actor',
]
```

This default matches `sirix/mezzio-authentication`, which stores the authenticated actor in `sirix.authentication.actor`.

If the request attribute contains an RBAC `ActorInterface`, it is used directly. If it contains an authentication-like object with `getRoles()`, it is adapted to an RBAC actor. Missing or invalid actor values fall back to `GuestActor`.

`ContainerActorProvider` is still available for non-request usage through `GuardInterface`, but it should not be used to resolve the current HTTP user.

### Metadata resolution

`AuthorizeMiddleware` resolves permission metadata in this order:

1. request attribute `sirix.rbac.permission`;
2. matched route option `sirix.rbac.permission`;
3. if missing or empty, pass through without authorization.

Context follows the same order:

1. request attribute `sirix.rbac.context`;
2. matched route option `sirix.rbac.context`;
3. empty array.

Context values map request attributes into rule context:

```php
[
    'postId' => 'id', // context['postId'] = $request->getAttribute('id')
]
```

### With standard Mezzio routing

Register `AuthorizeMiddleware` in your route pipeline and set permission/context as route options:

```php
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\RbacAttribute;

$app->post('/posts/:id', [
    AuthorizeMiddleware::class,
    PostHandler::class,
], 'post.update')->setOptions([
    RbacAttribute::Permission->value => 'posts.update',
    RbacAttribute::Context->value => ['postId' => 'id'],
]);
```

You can also set request attributes before `AuthorizeMiddleware` runs. Request attributes take precedence over route options.

### With `sirix/mezzio-routing-attributes`

When used with `sirix/mezzio-routing-attributes:^1.0`, `#[Can]` implements `RouteAttributeModifierInterface`. It injects `AuthorizeMiddleware` into the route pipeline and stores permission/context in route options.

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirix\Mezzio\Rbac\Attribute\Can;
use Sirix\Mezzio\Routing\Attributes\Attribute\Post;

#[Post('/posts/:id', name: 'post.update')]
#[Can('posts.update', ['postId' => 'id'])]
final class PostHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Authorization has already run before the handler.
    }
}
```

No manual middleware registration is needed for routes discovered by `sirix/mezzio-routing-attributes`.

## Integration with `sirix/mezzio-authentication`

`sirix/mezzio-authentication` writes the current actor to request attribute `sirix.authentication.actor`. RBAC uses that attribute by default, so the usual route pipeline is:

```text
AuthenticateMiddleware
  -> request attribute sirix.authentication.actor
  -> AuthorizeMiddleware
  -> RequestGuard
  -> permission lookup/rules
```

With attributes:

```php
use Sirix\Mezzio\Authentication\Attribute\Authenticated;
use Sirix\Mezzio\Rbac\Attribute\Can;
use Sirix\Mezzio\Routing\Attributes\Attribute\Get;

#[Get('/admin', name: 'admin')]
#[Authenticated]
#[Can('admin.access')]
final class AdminHandler implements RequestHandlerInterface
{
    // ...
}
```

Expected behavior:

- anonymous user is stopped by authentication;
- authenticated non-admin user receives `403`;
- authenticated admin user receives `200`.

## Manual authorization from services

For services without a request, use `GuardInterface`:

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

Read-only lookup contract used by authorization internals:

- `Sirix\Mezzio\Rbac\Contract\PermissionLookupInterface`

Default implementation:

- `Sirix\Mezzio\Rbac\InMemoryPermissionStore`

Later adapters can replace storage without changing the guard API.

## Extensibility

### Custom non-request actor provider

Use `ActorProviderInterface` for non-request authorization through `GuardInterface`:

```php
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;

final readonly class MyActorProvider implements ActorProviderInterface
{
    public function __construct(private MyAuthService $auth) {}

    public function getActor(): ActorInterface
    {
        $user = $this->auth->getIdentity();

        return new Actor($user?->getRoles() ?? ['guest']);
    }
}
```

### Custom request actor provider

Use `RequestActorProviderInterface` for HTTP authorization through `RequestGuardInterface` / `AuthorizeMiddleware`:

```php
use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Actor\Actor;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;

final readonly class MyRequestActorProvider implements RequestActorProviderInterface
{
    public function getActor(ServerRequestInterface $request): ActorInterface
    {
        $user = $request->getAttribute('user');

        return new Actor($user?->roles() ?? ['guest']);
    }
}
```

Register it in your dependencies:

```php
'dependencies' => [
    'factories' => [
        RequestActorProviderInterface::class => MyRequestActorProviderFactory::class,
    ],
],
```

### Custom Permission Store

Implement `PermissionStoreInterface` to load permissions from a database, cache, or another source:

```php
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;

final readonly class DatabasePermissionStore implements PermissionStoreInterface
{
    public function associationsForRole(string $role): array
    {
        // Fetch from DB and map to PermissionAssociation objects.
    }

    // ... implement other methods
}
```

### Custom Rules

As shown in the [Rules](#rules) section, implement `RuleInterface` to add dynamic logic to permissions. Rules are resolved through `RuleResolver`, which can use the PSR-11 container or instantiate rule classes directly.

## Main Components

- `GuardInterface` / `Guard`
- `RequestGuardInterface` / `RequestGuard`
- `ActorProviderInterface`
- `RequestActorProviderInterface`
- `RequestAttributeActorProvider`
- `Permissions`
- `PermissionLookupInterface`
- `PermissionMatcher`
- `RuleResolver`
- `InMemoryPermissionStore`
- `AuthorizeMiddleware`
- `RbacAttribute`
- `#[Can(...)]`
