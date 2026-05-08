<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface PermissionsInterface
{
    public function addRole(string $role): void;

    /**
     * @param null|class-string<RuleInterface>|RuleInterface $rule
     */
    public function associate(string $role, string $permissionPattern, RuleInterface|string|null $rule = null): void;
}
