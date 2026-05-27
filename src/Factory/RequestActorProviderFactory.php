<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sirix\Mezzio\Rbac\Actor\GuestActor;
use Sirix\Mezzio\Rbac\Actor\RequestAttributeActorProvider;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;

final class RequestActorProviderFactory
{
    private const DEFAULT_ACTOR_ATTRIBUTE = 'sirix.authentication.actor';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RequestActorProviderInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $attributeName = $config['rbac']['request_actor_attribute'] ?? self::DEFAULT_ACTOR_ATTRIBUTE;

        return new RequestAttributeActorProvider(
            '' === $attributeName ? self::DEFAULT_ACTOR_ATTRIBUTE : (string) $attributeName,
            $container->get(GuestActor::class),
        );
    }
}
