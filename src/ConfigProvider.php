<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionMapInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\Extractor\CanAttributeExtractor;
use Sirix\Mezzio\Rbac\Factory\ActorProviderFactory;
use Sirix\Mezzio\Rbac\Factory\AuthorizeMiddlewareFactory;
use Sirix\Mezzio\Rbac\Factory\GuardFactory;
use Sirix\Mezzio\Rbac\Factory\PermissionsFactory;
use Sirix\Mezzio\Rbac\Factory\RuleResolverFactory;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;
use Sirix\Mezzio\Rbac\Rule\AllowRule;
use Sirix\Mezzio\Rbac\Rule\ForbidRule;

final readonly class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                GuardInterface::class => GuardFactory::class,
                PermissionsInterface::class => PermissionsFactory::class,
                PermissionMapInterface::class => PermissionsFactory::class,
                PermissionStoreInterface::class => InMemoryPermissionStore::class,
                RuleResolver::class => RuleResolverFactory::class,
                ActorProviderInterface::class => ActorProviderFactory::class,
                AuthorizeMiddleware::class => AuthorizeMiddlewareFactory::class,
            ],
            'invokables' => [
                PermissionMatcher::class => PermissionMatcher::class,
                GuestActor::class => GuestActor::class,
                AllowRule::class => AllowRule::class,
                ForbidRule::class => ForbidRule::class,
                InMemoryPermissionStore::class => InMemoryPermissionStore::class,
                CanAttributeExtractor::class => CanAttributeExtractor::class,
            ],
            'aliases' => [
                Permissions::class => PermissionsInterface::class,
            ],
        ];
    }
}
