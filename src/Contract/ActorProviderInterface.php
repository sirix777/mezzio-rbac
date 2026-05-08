<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface ActorProviderInterface
{
    public function getActor(): ActorInterface;
}
