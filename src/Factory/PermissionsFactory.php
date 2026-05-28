<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\PermissionMatcher;
use Sirix\Mezzio\Rbac\Permissions;

final class PermissionsFactory
{
    /**
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): PermissionsInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new Permissions(
            $containerResolver->get(PermissionMatcher::class),
            $containerResolver->get(PermissionStoreInterface::class),
        );
    }
}
