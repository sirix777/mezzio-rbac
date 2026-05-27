<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Actor;

use Psr\Http\Message\ServerRequestInterface;
use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\RequestActorProviderInterface;

use function is_callable;
use function is_object;

final readonly class RequestAttributeActorProvider implements RequestActorProviderInterface
{
    public function __construct(private string $attributeName, private ActorInterface $guestActor) {}

    public function getActor(ServerRequestInterface $request): ActorInterface
    {
        $actor = $request->getAttribute($this->attributeName);

        if ($actor instanceof ActorInterface) {
            return $actor;
        }

        if (is_object($actor) && is_callable([$actor, 'getRoles'])) {
            return new GenericActorAdapter($actor);
        }

        return $this->guestActor;
    }
}
