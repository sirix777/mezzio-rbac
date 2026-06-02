<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;

final class InMemoryPermissionStore implements PermissionStoreInterface
{
    /**
     * @var array<string, true>
     */
    private array $roles = [];

    /**
     * @var array<string, list<PermissionAssociationInterface>>
     */
    private array $associations = [];

    private int $sequence = 0;

    public function addRole(string $role): void
    {
        $this->roles[$role] = true;
    }

    public function hasRole(string $role): bool
    {
        return isset($this->roles[$role]);
    }

    public function nextPriority(): int
    {
        return ++$this->sequence;
    }

    public function addAssociation(PermissionAssociationInterface $permissionAssociation): void
    {
        $this->associations[$permissionAssociation->getRole()][] = $permissionAssociation;
    }

    public function associationsForRole(string $role): array
    {
        return $this->associations[$role] ?? [];
    }
}
