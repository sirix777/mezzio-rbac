<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Mezzio\Rbac\Actor\ContainerActorProvider;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;

final class ActorProviderFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ActorProviderInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);

        return new ContainerActorProvider(
            $container,
            $containerResolver->get(GuestActor::class),
        );
    }
}
