<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface PermissionAssociationInterface
{
    public function getRole(): string;

    public function getPattern(): string;

    /**
     * @return null|class-string<RuleInterface>|RuleInterface
     */
    public function getRule(): RuleInterface|string|null;

    public function getPriority(): int;
}
