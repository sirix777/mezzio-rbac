<?php

declare(strict_types=1);

namespace SirixTest\Mezzio\Rbac\TestAsset;

use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;

use function array_reverse;

final class PermissionStoreStub implements PermissionStoreInterface
{
    private int $priority = 0;

    /**
     * @param list<PermissionAssociationInterface> $associations
     */
    public function __construct(private array $associations = [], private readonly bool $reverseAssociations = false) {}

    public function addRole(string $role): void {}

    public function hasRole(string $role): bool
    {
        return true;
    }

    public function nextPriority(): int
    {
        return ++$this->priority;
    }

    public function addAssociation(PermissionAssociationInterface $permissionAssociation): void
    {
        $this->associations[] = $permissionAssociation;
    }

    public function associationsForRole(string $role): array
    {
        return $this->reverseAssociations ? array_reverse($this->associations) : $this->associations;
    }
}
