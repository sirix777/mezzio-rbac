<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Sirix\Mezzio\Rbac\Contract\ActorProviderInterface;
use Sirix\Mezzio\Rbac\Contract\GuardInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\PermissionMapInterface;
use Sirix\Mezzio\Rbac\Exception\AuthorizationException;

final readonly class Guard implements GuardInterface
{
    public function __construct(
        private ActorProviderInterface $actorProvider,
        private PermissionMapInterface $permissions,
        private RuleResolver $ruleResolver,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function allows(string $permission, array $context = []): bool
    {
        $actor = $this->actorProvider->getActor();

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

    /**
     * @param array<string, mixed> $context
     */
    public function denies(string $permission, array $context = []): bool
    {
        return ! $this->allows($permission, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws AuthorizationException
     */
    public function authorize(string $permission, array $context = []): void
    {
        if ($this->denies($permission, $context)) {
            throw new AuthorizationException($permission, 'Forbidden');
        }
    }
}
