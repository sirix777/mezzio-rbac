<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Actor;

use Sirix\Mezzio\Rbac\Contract\ActorInterface;

final readonly class Actor implements ActorInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(private array $roles) {}

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
