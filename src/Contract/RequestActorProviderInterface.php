<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

use Psr\Http\Message\ServerRequestInterface;

interface RequestActorProviderInterface
{
    public function getActor(ServerRequestInterface $request): ActorInterface;
}
