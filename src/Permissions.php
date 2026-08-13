<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use InvalidArgumentException;
use LogicException;
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionLookupInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionsInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionStoreInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;

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
        $role              = trim($role);
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

        $this->permissionMatcher->specificity($permissionPattern);

        $this->permissionStore->addAssociation(
            new PermissionAssociation(
                role: $role,
                pattern: $permissionPattern,
                rule: $rule,
                priority: $this->permissionStore->nextPriority(),
            ),
        );
    }

    public function bestAssociationForRole(string $role, string $permission): ?PermissionAssociationInterface
    {
        $best            = null;
        $bestPriority    = PHP_INT_MIN;
        $bestSpecificity = [
            'exactSegments' => -1,
            'segmentCount'  => -1,
        ];
        $priorities      = [];

        foreach ($this->permissionStore->associationsForRole($role) as $permissionAssociation) {
            if ($permissionAssociation->getRole() !== $role) {
                throw new LogicException('Permission store returned an association for another role.');
            }

            $priority = $permissionAssociation->getPriority();
            if (isset($priorities[$priority])) {
                throw new LogicException('Permission store returned associations with duplicate priorities.');
            }

            $priorities[$priority] = true;
            $pattern               = $permissionAssociation->getPattern();
            $specificity           = $this->permissionMatcher->specificity($pattern);

            if (! $this->permissionMatcher->matches($pattern, $permission)) {
                continue;
            }

            if (
                null === $best
                || $this->isMoreSpecific($specificity, $bestSpecificity)
                || ($specificity === $bestSpecificity && $priority > $bestPriority)
            ) {
                $best            = $permissionAssociation;
                $bestPriority    = $priority;
                $bestSpecificity = $specificity;
            }
        }

        return $best;
    }

    /**
     * @param array{exactSegments: int, segmentCount: int} $candidate
     * @param array{exactSegments: int, segmentCount: int} $current
     */
    private function isMoreSpecific(array $candidate, array $current): bool
    {
        return $candidate['exactSegments'] > $current['exactSegments']
            || (
                $candidate['exactSegments'] === $current['exactSegments']
                && $candidate['segmentCount'] > $current['segmentCount']
            );
    }
}
