<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Mezzio\Rbac\AuthorizationEvaluator;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\RequestGuard;

final class RequestGuardFactory
{
    /**
     * @throws ResolverException
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
