<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Guard;

final class GuardFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): GuardInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new Guard(
            $containerResolver->get(ActorProviderInterface::class),
            $containerResolver->get(AuthorizationEvaluator::class),
        );
    }
}
