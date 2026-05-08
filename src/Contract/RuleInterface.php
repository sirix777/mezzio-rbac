<?php

declare(strict_types=1);

namespace Sirix\Mezzio\Rbac\Contract;

interface RuleInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function allows(ActorInterface $actor, string $permission, array $context): bool;
}
