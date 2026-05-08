<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface PermissionStoreInterface
{
    public function addRole(string $role): void;

    public function hasRole(string $role): bool;

    public function nextPriority(): int;

    public function addAssociation(PermissionAssociationInterface $association): void;

    /**
     * @return list<PermissionAssociationInterface>
     */
    public function associationsForRole(string $role): array;
}
