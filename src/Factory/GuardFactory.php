<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionMapInterface;
use Sirix\Mezzio\Rbac\Guard;
use Sirix\Mezzio\Rbac\RuleResolver;

final class GuardFactory
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): GuardInterface
    {
        return new Guard(
            $container->get(ActorProviderInterface::class),
            $container->get(PermissionMapInterface::class),
            $container->get(RuleResolver::class),
        );
    }
}
