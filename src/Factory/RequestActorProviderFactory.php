<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Actor\RequestAttributeActorProvider;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;

final class RequestActorProviderFactory
{
    private const DEFAULT_ACTOR_ATTRIBUTE = 'sirix.authentication.actor';

    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RequestActorProviderInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $configReader      = ConfigReader::fromContainer($containerResolver);

        return new RequestAttributeActorProvider(
            $configReader->nonEmptyString('rbac.request_actor_attribute', self::DEFAULT_ACTOR_ATTRIBUTE),
            $containerResolver->get(GuestActor::class),
        );
    }
}
