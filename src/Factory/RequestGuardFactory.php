<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\RequestGuard;

final class RequestGuardFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RequestGuardInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new RequestGuard(
            $containerResolver->get(RequestActorProviderInterface::class),
            $containerResolver->get(AuthorizationEvaluator::class),
        );
    }
}
