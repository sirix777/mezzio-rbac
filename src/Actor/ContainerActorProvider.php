<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Actor;

use Psr\Container\ContainerInterface;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;

final readonly class ContainerActorProvider implements ActorProviderInterface
{
    public function __construct(private ContainerInterface $container, private ActorInterface $guestActor) {}

    public function getActor(): ActorInterface
    {
        if (! $this->container->has(ActorInterface::class)) {
            return $this->guestActor;
        }

        $actor = $this->container->get(ActorInterface::class);

        return $actor instanceof ActorInterface ? $actor : $this->guestActor;
    }
}
