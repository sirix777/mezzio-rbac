<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac;

use Sirix\Mezzio\Rbac\Contract\PermissionAssociationInterface;
use Sirix\Mezzio\Rbac\Contract\RuleInterface;

final readonly class PermissionAssociation implements PermissionAssociationInterface
{
    /**
     * @param null|class-string<RuleInterface>|RuleInterface $rule
     */
    public function __construct(
        public string $role,
        public string $pattern,
        public RuleInterface|string|null $rule,
        public int $priority
    ) {}

    public function getRole(): string
    {
        return $this->role;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getRule(): RuleInterface|string|null
    {
        return $this->rule;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
