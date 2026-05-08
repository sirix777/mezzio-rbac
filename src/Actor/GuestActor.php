<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Actor;

use Sirix\Mezzio\Rbac\Contract\ActorInterface;

final readonly class GuestActor implements ActorInterface
{
    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['guest'];
    }
}
