<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Sirix\Mezzio\Rbac\Contract\ActorInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionMapInterface;

final readonly class AuthorizationEvaluator
{
    public function __construct(private PermissionMapInterface $permissions, private RuleResolver $ruleResolver) {}

    /**
     * @param array<string, mixed> $context
     */
    public function allows(ActorInterface $actor, string $permission, array $context = []): bool
    {
        foreach ($actor->getRoles() as $role) {
            $association = $this->permissions->bestAssociationForRole(
                $role,
                $permission,
            );

            if (! $association instanceof PermissionAssociationInterface) {
                continue;
            }

            $rule = $this->ruleResolver->resolve($association->getRule());
            if ($rule->allows($actor, $permission, $context)) {
                return true;
            }
        }

        return false;
    }
}
