<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface ActorInterface
{
    /**
     * @return list<string>
     */
    public function getRoles(): array;
}
