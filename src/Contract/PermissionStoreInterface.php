<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface PermissionStoreInterface
{
    public function addRole(string $role): void;

    public function hasRole(string $role): bool;

    /**
     * Return a priority unique among associations held by this store.
     *
     * Higher priorities take precedence when matching associations have the
     * same specificity.
     */
    public function nextPriority(): int;

    public function addAssociation(PermissionAssociationInterface $permissionAssociation): void;

    /**
     * @return list<PermissionAssociationInterface>
     *
     * Every association must belong to the requested role and have a unique
     * priority
     */
    public function associationsForRole(string $role): array;
}
