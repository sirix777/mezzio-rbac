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
    public function __construct(private PermissionMatcher $matcher, private PermissionStoreInterface $store) {}

    public function addRole(string $role): void
    {
        $role = trim($role);
        if ('' === $role) {
            throw new InvalidArgumentException(
                'Role must be a non-empty string.',
            );
        }

        $this->store->addRole($role);
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

        if (! $this->store->hasRole($role)) {
            throw new InvalidArgumentException(
                "Role '{$role}' must be registered before association.",
            );
        }

        $this->store->addAssociation(
            new PermissionAssociation(
                role: $role,
                pattern: $permissionPattern,
                rule: $rule,
                priority: $this->store->nextPriority(),
                specificity: $this->matcher->specificity($permissionPattern),
            ),
        );
    }

    public function bestAssociationForRole(string $role, string $permission): ?PermissionAssociationInterface
    {
        $best = null;

        foreach (
            array_reverse($this->store->associationsForRole($role)) as $association
        ) {
            if (
                ! $this->matcher->matches(
                    $association->getPattern(),
                    $permission,
                )
            ) {
                continue;
            }

            if (
                null === $best
                || $association->getSpecificity() > $best->getSpecificity()
            ) {
                $best = $association;
            }
        }

        return $best;
    }
}
