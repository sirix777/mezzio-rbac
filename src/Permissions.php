<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use InvalidArgumentException;
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionLookupInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;

use function array_reverse;
use function trim;

final readonly class Permissions implements PermissionsInterface, PermissionLookupInterface
{
    public function __construct(private PermissionMatcher $permissionMatcher, private PermissionStoreInterface $permissionStore) {}

    public function addRole(string $role): void
    {
        $role = trim($role);
        if ('' === $role) {
            throw new InvalidArgumentException(
                'Role must be a non-empty string.',
            );
        }

        $this->permissionStore->addRole($role);
    }

    /**
     * @param null|class-string<RuleInterface>|RuleInterface $rule
     */
    public function associate(string $role, string $permissionPattern, RuleInterface|string|null $rule = null): void
    {
        $role = trim($role);
        $permissionPattern = trim($permissionPattern);

        if ('' === $role) {
            throw new InvalidArgumentException(
                'Role must be a non-empty string.',
            );
        }

        if ('' === $permissionPattern) {
            throw new InvalidArgumentException(
                'Permission pattern must be a non-empty string.',
            );
        }

        if (! $this->permissionStore->hasRole($role)) {
            throw new InvalidArgumentException(
                "Role '{$role}' must be registered before association.",
            );
        }

        $this->permissionStore->addAssociation(
            new PermissionAssociation(
                role: $role,
                pattern: $permissionPattern,
                rule: $rule,
                priority: $this->permissionStore->nextPriority(),
                specificity: $this->permissionMatcher->specificity($permissionPattern),
            ),
        );
    }

    public function bestAssociationForRole(string $role, string $permission): ?PermissionAssociationInterface
    {
        $best = null;

        foreach (
            array_reverse($this->permissionStore->associationsForRole($role)) as $permissionAssociation
        ) {
            if (
                ! $this->permissionMatcher->matches(
                    $permissionAssociation->getPattern(),
                    $permission,
                )
            ) {
                continue;
            }

            if (
                null === $best
                || $permissionAssociation->getSpecificity() > $best->getSpecificity()
            ) {
                $best = $permissionAssociation;
            }
        }

        return $best;
    }
}
