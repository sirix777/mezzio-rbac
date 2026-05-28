<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;
use Sirix\Mezzio\Rbac\Contract\RequestGuardInterface;
use Sirix\Mezzio\Rbac\Middleware\AuthorizeMiddleware;

final class AuthorizeMiddlewareFactory
{
    /**
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container): AuthorizeMiddleware
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new AuthorizeMiddleware(
            $containerResolver->get(RequestGuardInterface::class),
        );
    }
}
