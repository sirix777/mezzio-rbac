<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface PermissionLookupInterface
{
    public function bestAssociationForRole(string $role, string $permission): ?PermissionAssociationInterface;
}
