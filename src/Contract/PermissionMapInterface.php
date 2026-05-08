<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface PermissionMapInterface
{
    public function bestAssociationForRole(string $role, string $permission): ?PermissionAssociationInterface;
}
